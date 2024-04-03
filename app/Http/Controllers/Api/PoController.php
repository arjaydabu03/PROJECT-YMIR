<?php

namespace App\Http\Controllers\Api;

use App\Models\POItems;
use App\Models\PoHistory;
use App\Response\Message;
use App\Models\PoApprovers;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;

class PoController extends Controller
{
    public function index(PRViewRequest $request)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = POTransaction::orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $purchase_order->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        // PRTransactionResource::collection($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_order
        );
    }

    public function store(Request $request)
    {
        $user_id = Auth()->user()->id;

        $orders = $request->order;

        $po_number = POTransaction::latest()
            ->get()
            ->first();
        $increment = $po_number ? $po_number->id + 1 : 1;

        $purchase_order = new POTransaction([
            "po_number" => $increment,
            "pr_number" => $request->pr_number,
            "po_description" => $request->po_description,
            "date_needed" => $request->date_needed,
            "user_id" => $request->user_id,
            "type_id" => $request->type_id,
            "type_name" => $request->type_name,
            "business_unit_id" => $request->business_unit_id,
            "business_unit_name" => $request->business_unit_name,
            "company_id" => $request->company_id,
            "company_name" => $request->company_name,
            "department_id" => $request->department_id,
            "department_name" => $request->department_name,
            "department_unit_id" => $request->department_unit_id,
            "department_unit_name" => $request->department_unit_name,
            "location_id" => $request->location_id,
            "location_name" => $request->location_name,
            "sub_unit_id" => $request->sub_unit_id,
            "sub_unit_name" => $request->sub_unit_name,
            "account_title_id" => $request->account_title_id,
            "account_title_name" => $request->account_title_name,
            "module_name" => $request->module_name,
            "status" => "Pending",
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "layer" => "1",
            "description" => $request->description,
        ]);
        $purchase_order->save();

        foreach ($orders as $index => $values) {
            POItems::create([
                "po_id" => $purchase_order->id,
                "pr_id" => $purchase_order->pr_number,
                "item_id" => $request["order"][$index]["item_id"],
                "item_code" => $request["order"][$index]["item_code"],
                "item_name" => $request["order"][$index]["item_name"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "supplier_id" => $request["order"][$index]["supplier_id"],
                "quantity" => $request["order"][$index]["quantity"],
                "buyer_id" => $request["order"][$index]["buyer_id"],
                "remarks" => $request["order"][$index]["remarks"],
            ]);
        }

        $approvers = PoApprovers::get();

        foreach ($approvers as $index) {
            PoHistory::create([
                "po_id" => $purchase_order->id,
                "approver_id" => $index["approver_id"],
                "approver_name" => $index["approver_name"],
                "layer" => $index["layer"],
            ]);
        }

        // $pr_collect = new PRTransactionResource($purchase_order);

        return GlobalFunction::save(
            Message::PURCHASE_ORDER_SAVE,
            $purchase_order
        );
    }

    public function update(Request $request, $id)
    {
        $purchase_order = POTransaction::find($id);
        $not_found = POTransaction::where("id", $id)->exists();

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

        $purchase_order->update([
            "pr_number" => $purchase_order->pr_number,
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
            "supplier_id" => $request->supplier_id,
            "supplier_name" => $request->supplier_name,
            "module_name" => "Inventoriables",
            "description" => $request->description,
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
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
            POItems::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "transaction_id" => $purchase_order->id,
                    "item_id" => $values["item_id"],
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "supplier_id" => $values["supplier_id"],
                    "quantity" => $values["quantity"],
                    "quantity_serve" => $values["quantity_serve"],
                    "buyer_id" => $request["order"][$index]["buyer_id"],
                    "remarks" => $values["remarks"],
                ]
            );
        }

        $pr_collect = new PRTransactionResource($purchase_order);

        return GlobalFunction::save(
            Message::PURCHASE_ORDER_UPDATE,
            $pr_collect
        );
    }

    public function destroy($id)
    {
        $purchase_order = POTransaction::where("id", $id)
            ->withTrashed()
            ->get();

        if ($purchase_order->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_order = POTransaction::withTrashed()->find($id);
        $is_active = POTransaction::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $purchase_order->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $purchase_order->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $purchase_order);
    }
}
