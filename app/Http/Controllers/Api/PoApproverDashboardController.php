<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\PoHistory;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Functions\GlobalFunction;
use App\Http\Resources\PoResource;
use App\Http\Controllers\Controller;

class PoApproverDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth()->user()->id;

        $status = $request->status;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $po_id = PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("po_id");
        $layer = PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        if (empty($po_id) || empty($layer)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request = POTransaction::with("order", "approver_history")
            ->when($status == "pending", function ($query) use (
                $po_id,
                $layer
            ) {
                $query

                    ->whereIn("id", $po_id)
                    ->whereIn("layer", $layer)
                    ->where("status", "Pending")
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at");
            })
            ->when($status == "rejected", function ($query) use (
                $po_id,
                $layer
            ) {
                $query
                    ->whereIn("id", $po_id)
                    ->whereIn("layer", $layer)
                    ->whereNull("voided_at")
                    ->whereNotNull("rejected_at");
            })

            ->when($status == "approved", function ($query) use (
                $po_id, 
                $layer,
                $user_id
            ) {
                $query
                    ->whereIn("id", $po_id)
                    ->whereHas("approver_history", function ($query) use (
                        $user_id
                    ) {
                        $query
                            ->whereIn("approver_id", $user_id)
                            ->whereNotNull("approved_at");
                    });
            })
            ->get();

        if ($purchase_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        $purchase_collect = PoResource::collection($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_collect
        );
    }

    public function approved(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $set_approver = PoHistory::where("po_id", $id)->get();

        if (empty($set_approver)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $approved_history = PoHistory::where("po_id", $id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "approved_at" => $date_today,
            ]);

        $count = count($set_approver);

        $po_transaction = POTransaction::find($id);

        if ($count == $po_transaction->layer) {
            $po_transaction->update([
                "approved_at" => $date_today,
                "status" => "Approved",
            ]);
            $po_collect = new PoResource($po_transaction);
            return GlobalFunction::responseFunction(
                Message::APPORVED,
                $po_collect
            );
        }
        $po_transaction->update([
            "layer" => $po_transaction->layer + 1,
            "status" => "For approval",
        ]);

        $po_collect = new PoResource($po_transaction);
        return GlobalFunction::responseFunction(Message::APPORVED, $po_collect);
    }

    public function cancelled(Request $request, $id)
    {
        $user = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $po_transaction = POTransaction::find($id)
            ->where("user_id", $user)
            ->get()
            ->first();

        if ($po_transaction->status == "For approval") {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $po_transaction->update([
            "cancelled_at" => $date_today,
        ]);
        $po_collect = new PoResource($po_transaction);

        return GlobalFunction::responseFunction(
            Message::CANCELLED,
            $po_collect
        );
    }
    public function voided(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $po_transaction = POTransaction::find($id);

        $po_transaction->update([
            "status" => "Voided",
            "voided_at" => $date_today,
        ]);

        $po_collect = new PoResource($po_transaction);
        return GlobalFunction::responseFunction(Message::VOIDED, $po_collect);
    }
    public function rejected(RejectRequest $request, $id)
    {
        $user = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $po_transaction = POTransaction::find($id);

        $po_transaction->update([
            "status" => "Reject",
            "reason" => $request["reason"],
            "rejected_at" => $date_today,
        ]);

        $to_reject = PoHistory::where("po_id", $po_transaction->id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "rejected_at" => $date_today,
            ]);

        $po_collect = new PoResource($po_transaction);
        return GlobalFunction::responseFunction(Message::REJECTED, $po_collect);
    }
}
