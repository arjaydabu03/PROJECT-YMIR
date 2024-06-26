<?php

namespace App\Http\Controllers\Api;

use App\Models\Buyer;
use App\Models\POItems;
use App\Models\PoHistory;
use App\Response\Message;
use App\Models\POSettings;
use App\Models\PoApprovers;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Http\Requests\BDisplay;
use App\Functions\GlobalFunction;
use App\Http\Requests\BPADisplay;
use App\Http\Resources\PoResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\PRPOResource;
use App\Http\Resources\PRTransactionResource;

class BuyerController extends Controller
{
    public function index(BDisplay $request)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_request = Buyer::with(
            "order",
            "approver_history",
            "po_transaction",
            "po_transaction.order",
            "po_transaction.approver_history"
        )
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $purchase_request->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        PRTransactionResource::collection($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_request
        );
    }

    public function view(Request $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = POTransaction::where("id", $id)
            ->with([
                "order" => function ($query) use ($user_id) {
                    $query->where("buyer_id", $user_id);
                },
                "approver_history",
            ])
            ->orderByDesc("updated_at")
            ->get()
            ->first();

        if (!$purchase_order) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        new PoResource($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_order
        );
    }

    public function viewto_po(Request $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        return $purchase_order = Buyer::with([
            // "order" => function ($query) use ($user_id) {
            //     // $query->where("buyer_id", $user_id);
            //     // $query->whereNull("supplier_id")->where("buyer_id", $user_id);
            // },
            "order",
            "approver_history",
            "po_transaction",
            "po_transaction.order",
        ])
            ->where("id", $id)
            ->orderByDesc("updated_at")
            ->first();

        if (!$purchase_order) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        new PRPOResource($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_order
        );
    }

    public function update(Request $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_request = Buyer::with([
            "order" => function ($query) use ($user_id) {
                return $query->where("buyer_id", $user_id);
            },
            "approver_history",
            "po_transaction",
            "po_transaction.order" => function ($query) use ($user_id) {
                return $query->where("buyer_id", $user_id);
            },
            "po_transaction.approver_history",
        ])
            ->where("id", $id)
            ->first();

        $poTransaction = $purchase_request->po_transaction->first();
        $po_order = $poTransaction->order();

        $order = $request->orders;

        foreach ($order as $index => $values) {
            $order_id = $values["id"];
            $poItem = POItems::find($order_id);
            if ($poItem) {
                $poItem->update([
                    "price" => $values["price"],
                    "total_price" => $poItem->quantity * $values["price"],
                ]);
            }
        }

        $po_settings = POSettings::where(
            "company_id",
            $poTransaction->company_id
        )
            ->get()
            ->first();

        $purchase_items = POItems::where("po_id", $poTransaction->id)
            ->get()
            ->pluck("total_price")
            ->toArray();

        $totalPriceSum = array_sum($purchase_items);

        if ($totalPriceSum >= 300001) {
            $poTransaction->update(["updated_by" => $user_id]);

            $po_approvers = $poTransaction->approver_history()->get();

            foreach ($po_approvers as $po_approver) {
                $po_approver->update(["approved_at" => null]);
            }

            $approvers = PoApprovers::where("price_range", ">=", 300001)->get();
            $po_approver_history = $poTransaction->approver_history()->first();

            foreach ($approvers as $index) {
                $existing_approver = PoHistory::where(
                    "po_id",
                    $po_approver_history->po_id
                )
                    ->where("approver_id", $index["approver_id"])
                    ->first();

                if (!$existing_approver) {
                    PoHistory::create([
                        "po_id" => $po_approver_history->po_id,
                        "approver_id" => $index["approver_id"],
                        "approver_name" => $index["approver_name"],
                        "layer" => $index["layer"],
                    ]);
                }
            }
        }

        new PoResource($poTransaction);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_UPDATE,
            $poTransaction
        );
    }
}
