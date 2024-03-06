<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\PRItems;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\PRTransaction;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\PRTransactionResource;
use App\Http\Requests\PurchaseRequest\StoreRequest;

class PRTransactionController extends Controller
{
    public function index(PRViewRequest $request)
    {
        $user_id = Auth()->user()->id;
        $status = $request->status;
        $purchase_request = PRTransaction::where("user_id", $user_id)
            ->useFilters()
            ->orderByDesc("updated_at")
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

    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;

        $orders = $request->order;

        $pr_number = PRTransaction::latest()
            ->get()
            ->first();
        $increment = $pr_number ? $pr_number->id + 1 : 1;

        $user_details = User::with(
            "company",
            "business_unit",
            "department",
            "department_unit",
            "sub_unit",
            "location"
        )
            ->where("id", $user_id)
            ->get()
            ->first();

        $purchase_request = new PRTransaction([
            "pr_number" => $increment,
            "pr_description" => $request["pr_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
            "type_id" => $request["type_id"],
            "type_name" => $request["type_name"],
            "business_unit_id" => $user_details->business_unit->id,
            "business_unit_name" => $user_details->business_unit->name,
            "company_id" => $user_details->company->id,
            "company_name" => $user_details->company->name,
            "department_id" => $user_details->department->id,
            "department_name" => $user_details->department->name,
            "department_unit_id" => $user_details->department_unit->id,
            "department_unit_name" => $user_details->department_unit->name,
            "location_id" => $user_details->location->id,
            "location_name" => $user_details->location->name,
            "sub_unit_id" => $user_details->sub_unit->id,
            "sub_unit_name" => $user_details->sub_unit->name,
            "account_title_id" => $request->account_title_id,
            "account_title_name" => $request->account_title_name,
            "module_name" => $request->module_name,
            "layer" => "1",
        ]);
        $purchase_request->save();

        foreach ($orders as $index => $values) {
            PRItems::create([
                "transaction_id" => $purchase_request->id,
                "item_id" => $request["order"][$index]["item_id"],
                "item_code" => $request["order"][$index]["item_code"],
                "item_name" => $request["order"][$index]["item_name"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "quantity" => $request["order"][$index]["quantity"],
                "canvas" => $request["order"][$index]["canvas"],
                "remarks" => $request["order"][$index]["remarks"],
            ]);
        }

        $pr_collect = new PRTransactionResource($purchase_request);

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_SAVE,
            $pr_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $purchase_request = PRTransaction::find($id);
        $not_found = PRTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::not_found(Message::NOT_FOUND);
        }
        $user_id = Auth()->user()->id;

        $orders = $request->order;

        $user_details = User::with(
            "company",
            "business_unit",
            "department",
            "department_unit",
            "sub_unit",
            "location"
        )
            ->where("id", $user_id)
            ->get()
            ->first();

        $purchase_request->update([
            "pr_number" => "1",
            "pr_description" => $request["pr_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
            "type_id" => $request["type_id"],
            "type_name" => $request["type_name"],
            "business_unit_id" => $user_details->business_unit->id,
            "business_unit_name" => $user_details->business_unit->name,
            "company_id" => $user_details->company->id,
            "company_name" => $user_details->company->name,
            "department_id" => $user_details->department->id,
            "department_name" => $user_details->department->name,
            "department_unit_id" => $user_details->department_unit->id,
            "department_unit_name" => $user_details->department_unit->name,
            "location_id" => $user_details->location->id,
            "location_name" => $user_details->location->name,
            "sub_unit_id" => $user_details->sub_unit->id,
            "sub_unit_name" => $user_details->sub_unit->name,
            "account_title_id" => $request->account_title_id,
            "account_title_name" => $request->account_title_name,
            "module_name" => $request->module_name,
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = PRItems::where("transaction_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                PRItems::where("id", $order_id)->forceDelete();
            }
        }

        foreach ($orders as $index => $values) {
            PRItems::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "transaction_id" => $purchase_request->id,
                    "item_id" => $values["item_id"],
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
                    "canvas" => $values["canvas"],
                    "remarks" => $values["remarks"],
                ]
            );
        }

        $pr_collect = new PRTransactionResource($purchase_request);

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_UPDATE,
            $pr_collect
        );
    }

    public function destroy($id)
    {
        $purchase_request = PRTransaction::where("id", $id)
            ->withTrashed()
            ->get();

        if ($purchase_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request = PRTransaction::withTrashed()->find($id);
        $is_active = PRTransaction::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $purchase_request->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $purchase_request->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $purchase_request);
    }
    public function cancelled(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $pr_transaction = PRTransaction::find($id);

        $pr_transaction->update([
            "cancelled_at" => $date_today,
        ]);
        $pr_collect = new PRTransactionResource($pr_transaction);

        return GlobalFunction::responseFunction(
            Message::CANCELLED,
            $pr_collect
        );
    }
    public function voided(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $pr_transaction = PRTransaction::find($id);

        $pr_transaction->update([
            "voided_at" => $date_today,
        ]);

        $pr_collect = new PRTransactionResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::VOIDED, $pr_collect);
    }
    public function rejected(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $pr_transaction = PRTransaction::find($id);

        $pr_transaction->update([
            "rejected_at" => $date_today,
        ]);
        $pr_collect = new PRTransactionResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::REJECTED, $pr_collect);
    }
    // public function cancelled(Request $request, $id)
    // {
    // }
}
