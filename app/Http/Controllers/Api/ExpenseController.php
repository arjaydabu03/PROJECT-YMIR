<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Expense;
use App\Models\PRItems;
use App\Models\PrHistory;
use App\Response\Message;
use App\Models\SetApprover;
use Illuminate\Http\Request;
use App\Models\PRTransaction;
use App\Models\ApproverSettings;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\PRPOResource;
use App\Http\Requests\Expense\StoreRequest;
use App\Http\Resources\PRTransactionResource;

class ExpenseController extends Controller
{
    public function index(PRViewRequest $request)
    {
        $user_id = Auth()->user()->id;
        $status = $request->status;
        $purchase_request = Expense::with(
            "order",
            "approver_history",
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
        PRPOResource::collection($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_request
        );
    }

    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;

        if ($request->has("for_po_only")) {
            $for_po_id = $user_id;
            $date_today = Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d H:i");
        } else {
            $for_po_id = null;
            $date_today = null;
        }

        $orders = $request->order;

        $pr_number = PRTransaction::latest()
            ->get()
            ->first();
        $increment = $pr_number ? $pr_number->id + 1 : 1;

        $purchase_request = new PRTransaction([
            "pr_number" => $increment,
            "pr_description" => $request["pr_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
            "type_id" => $request["type_id"],
            "type_name" => $request["type_name"],
            "business_unit_id" => $request["business_unit_id"],
            "business_unit_name" => $request["business_unit_name"],
            "company_id" => $request["company_id"],
            "company_name" => $request["company_name"],
            "department_id" => $request["department_id"],
            "department_name" => $request["department_name"],
            "department_unit_id" => $request["department_unit_id"],
            "department_unit_name" => $request["department_unit_name"],
            "location_id" => $request["location_id"],
            "location_name" => $request["location_name"],
            "sub_unit_id" => $request["sub_unit_id"],
            "sub_unit_name" => $request["sub_unit_name"],
            "account_title_id" => $request["account_title_id"],
            "account_title_name" => $request["account_title_name"],
            "module_name" => "Expense",
            "description" => $request["description"],
            "asset" => $request["asset"],
            "status" => "Pending",
            "sgp" => $request["sgp"],
            "f1" => $request["f1"],
            "f2" => $request["f2"],
            "for_po_only" => $date_today,
            "for_po_only_id" => $for_po_id,
            "layer" => "1",
        ]);
        $purchase_request->save();

        foreach ($orders as $index => $values) {
            PRItems::create([
                "transaction_id" => $purchase_request->id,
                "item_code" => $request["order"][$index]["item_code"],
                "item_name" => $request["order"][$index]["item_name"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "quantity" => $request["order"][$index]["quantity"],
                "remarks" => $request["order"][$index]["remarks"],
                "attachment" => $request["order"][$index]["attachment"],
            ]);
        }
        $approver_settings = ApproverSettings::where(
            "company_id",
            $purchase_request->company_id
        )
            ->where("business_unit_id", $purchase_request->business_unit_id)
            ->where("department_id", $purchase_request->department_id)
            ->where("department_unit_id", $purchase_request->department_unit_id)
            ->where("sub_unit_id", $purchase_request->sub_unit_id)
            ->where("location_id", $purchase_request->location_id)
            ->whereHas("set_approver")
            ->get()
            ->first();

        $approvers = SetApprover::where(
            "approver_settings_id",
            $approver_settings->id
        )->get();
        if ($approvers->isEmpty()) {
            return GlobalFunction::save(Message::NO_APPROVERS);
        }

        foreach ($approvers as $index) {
            PrHistory::create([
                "pr_id" => $purchase_request->id,
                "approver_id" => $index["approver_id"],
                "approver_name" => $index["approver_name"],
                "layer" => $index["layer"],
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

        $purchase_request->update([
            "pr_number" => $request["pr_number"],
            "pr_description" => $request["pr_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
            "type_id" => $request["type_id"],
            "type_name" => $request["type_name"],
            "business_unit_id" => $request["business_unit_id"],
            "business_unit_name" => $request["business_unit_name"],
            "company_id" => $request["company_id"],
            "company_name" => $request["company_name"],
            "department_id" => $request["department_id"],
            "department_name" => $request["department_name"],
            "department_unit_id" => $request["department_unit_id"],
            "department_unit_name" => $request["department_unit_name"],
            "location_id" => $request["location_id"],
            "location_name" => $request["location_name"],
            "sub_unit_id" => $request["sub_unit_id"],
            "sub_unit_name" => $request["sub_unit_name"],
            "account_title_id" => $request["account_title_id"],
            "account_title_name" => $request["account_title_name"],
            "supplier_id" => $request["supplier_id"],
            "supplier_name" => $request["supplier_name"],
            "module_name" => "Expense",
            "description" => $request["description"],
            "asset" => $request["asset"],
            "sgp" => $request["sgp"],
            "f1" => $request["f1"],
            "f2" => $request["f2"],
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
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
                    "remarks" => $values["remarks"],
                    "attachment" => $values["attachment"],
                ]
            );
        }

        $pr_collect = new PRTransactionResource($purchase_request);

        return GlobalFunction::save(Message::RESUBMITTED, $pr_collect);
    }

    public function resubmit(Request $request, $id)
    {
        $purchase_request = PRTransaction::find($id);

        $user_id = Auth()->user()->id;

        $not_found = PRTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::not_found(Message::NOT_FOUND);
        }

        $pr_history = PrHistory::where("pr_id", $id)->get();

        if ($pr_history->isEmpty()) {
            return GlobalFunction::not_found(Message::NOT_FOUND);
        }

        foreach ($pr_history as $pr) {
            $pr->update([
                "approved_at" => null,
                "rejected_at" => null,
            ]);
        }

        $orders = $request->order;

        $purchase_request->update([
            "pr_number" => $purchase_request->id,
            "pr_description" => $request["pr_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
            "type_id" => $request["type_id"],
            "type_name" => $request["type_name"],
            "business_unit_id" => $request["business_unit_id"],
            "business_unit_name" => $request["business_unit_name"],
            "company_id" => $request["company_id"],
            "company_name" => $request["company_name"],
            "department_id" => $request["department_id"],
            "department_name" => $request["department_name"],
            "department_unit_id" => $request["department_unit_id"],
            "department_unit_name" => $request["department_unit_name"],
            "location_id" => $request["location_id"],
            "location_name" => $request["location_name"],
            "sub_unit_id" => $request["sub_unit_id"],
            "sub_unit_name" => $request["sub_unit_name"],
            "account_title_id" => $request["account_title_id"],
            "account_title_name" => $request["account_title_name"],
            "supplier_id" => $request["supplier_id"],
            "supplier_name" => $request["supplier_name"],
            "status" => "Pending",
            "module_name" => "Expense",
            "approved_at" => null,
            "rejected_at" => null,
            "voided_at" => null,
            "cancelled_at" => null,
            "description" => $request["description"],
            "asset" => $request["asset"],
            "sgp" => $request["sgp"],
            "f1" => $request["f1"],
            "f2" => $request["f2"],
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
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
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
            "reason" => $request->reason,
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
            "reason" => $request->reason,
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
            "reason" => $request->reason,
        ]);
        $pr_collect = new PRTransactionResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::REJECTED, $pr_collect);
    }
}
