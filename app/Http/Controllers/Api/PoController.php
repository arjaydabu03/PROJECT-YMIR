<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\POItems;
use App\Models\PRItems;
use App\Models\JobItems;
use App\Models\PoHistory;
use App\Response\Message;
use App\Models\JobHistory;
use App\Models\JoPoOrders;
use App\Models\POSettings;
use App\Models\JoPoHistory;
use App\Models\PoApprovers;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\PRTransaction;
use App\Models\JOPOTransaction;
use App\Functions\GlobalFunction;
use App\Http\Resources\PoResource;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\JoPoResource;
use App\Http\Requests\JoPo\StoreRequest;

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
        PoResource::collection($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_order
        );
    }

    public function view(Request $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = POTransaction::where("id", $id)
            ->orderByDesc("updated_at")
            ->get()
            ->first();

        if (!$purchase_order) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        new PoResource($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_order
        );
    }

    public function approved_pr(Request $request)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = PRTransaction::with("order", "approver_history")
            ->orderByDesc("updated_at")
            ->whereNotNull("approved_at")
            ->where("status", "Approved")
            ->get();

        $is_empty = $purchase_order->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        PoResource::collection($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_order
        );
    }

    public function store(Request $request)
    {
        $user_id = Auth()->user()->id;

        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $orders = $request->order;

        $if_exist = PRTransaction::where("id", $request->pr_number)->get();

        if ($if_exist->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

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
            "total_item_price" => $request->total_item_price,
            "supplier_id" => $request->supplier_id,
            "supplier_name" => $request->supplier_name,
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
                "price" => $request["order"][$index]["price"],
                "quantity" => $request["order"][$index]["quantity"],
                "total_price" =>
                    $request["order"][$index]["price"] *
                    $request["order"][$index]["quantity"],
                "quantity_serve" => 0,
                "attachment" => $request["order"][$index]["attachment"],
                "buyer_id" => $request["order"][$index]["buyer_id"],
                "buyer_name" => $request["order"][$index]["buyer_name"],
                "remarks" => $request["order"][$index]["remarks"],
            ]);
        }

        foreach ($orders as $index => $values) {
            $item_id = $request["order"][$index]["id"];
            $items = PRItems::where("id", $item_id)->update([
                "po_at" => $date_today,
                "purchase_order_id" => $purchase_order->id,
                "supplier_id" => $request["order"][$index]["supplier_id"],
            ]);
        }

        $po_settings = POSettings::where(
            "company_id",
            $purchase_order->company_id
        )
            ->get()
            ->first();

        $purchase_items = POItems::where("po_id", $purchase_order->id)
            ->get()
            ->pluck("total_price")
            ->toArray();

        $sum = array_sum($purchase_items);

        $approvers = PoApprovers::where("price_range", "<=", $sum)->get();

        foreach ($approvers as $index) {
            PoHistory::create([
                "po_id" => $purchase_order->id,
                "approver_id" => $index["approver_id"],
                "approver_name" => $index["approver_name"],
                "layer" => $index["layer"],
            ]);
        }

        $po_collect = new PoResource($purchase_order);

        return GlobalFunction::save(Message::PURCHASE_ORDER_SAVE, $po_collect);
    }

    public function store_jo(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;

        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $orders = $request->order;

        $jo_number = JOPOTransaction::latest()
            ->get()
            ->first();
        $increment = $jo_number ? $jo_number->id + 1 : 1;

        $job_order = new JOPOTransaction([
            "po_number" => $increment,
            "jo_number" => $request->jo_number,
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
            "total_item_price" => $request->total_item_price,
            "status" => "Pending",
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "layer" => "1",
            "description" => $request->description,
        ]);
        $job_order->save();

        foreach ($orders as $index => $values) {
            JoPoOrders::create([
                "jo_po_id" => $job_order->id,
                "jo_transaction_id" => $job_order->jo_number,
                "description" => $request["order"][$index]["description"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "unit_price" => $request["order"][$index]["price"],
                "quantity" => $request["order"][$index]["quantity"],
                "quantity_serve" => 0,
                "total_price" =>
                    $request["order"][$index]["price"] *
                    $request["order"][$index]["quantity"],
                "attachment" => $request["order"][$index]["attachment"],
                "remarks" => $request["order"][$index]["remarks"],
                "asset" => $request["order"][$index]["asset"],
            ]);
        }

        foreach ($orders as $index => $values) {
            $item_id = $request["order"][$index]["id"];
            $items = JobItems::where("id", $item_id)->update([
                "po_at" => $date_today,
                "purchase_order_id" => $job_order->id,
            ]);
        }

        $po_settings = POSettings::where("company_id", $job_order->company_id)
            ->get()
            ->first();

        $jo_items = JoPoOrders::where("jo_po_id", $job_order->id)
            ->get()
            ->pluck("total_price")
            ->toArray();

        $sum = array_sum($jo_items);

        $approvers = PoApprovers::where("price_range", "<=", $sum)->get();

        foreach ($approvers as $index) {
            JoPoHistory::create([
                "jo_po_id" => $job_order->id,
                "approver_id" => $index["approver_id"],
                "approver_name" => $index["approver_name"],
                "layer" => $index["layer"],
            ]);
        }

        $jo_collect = new JoPoResource($job_order);

        return GlobalFunction::save(Message::JOB_ORDER_SAVE, $jo_collect);
    }

    public function update(Request $request, $id)
    {
        $purchase_order = POTransaction::find($id);

        $not_found = POTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::not_found(Message::NOT_FOUND);
        }

        $orders = $request->order;

        $purchase_order->update([
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
            "supplier_id" => $request->supplier_id,
            "supplier_name" => $request->supplier_name,
            "module_name" => $request->module_name,
            "status" => "Pending",
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "layer" => "1",
            "description" => $request->description,
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = POItems::where("po_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                POItems::where("id", $order_id)->forceDelete();
            }
        }

        foreach ($orders as $index => $values) {
            POItems::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "po_id" => $purchase_order->id,
                    "pr_id" => $purchase_order->pr_number,
                    "item_id" => $values["item_id"],
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "supplier_id" => $values["supplier_id"],
                    "price" => $values["price"],
                    "quantity" => $values["quantity"],
                    "quantity_serve" => $values["quantity_serve"],
                    "total_price" => $values["total_price"],
                    "attachment" => $values["attachment"],
                    "buyer_id" => $values["buyer_id"],
                    "buyer_name" => $values["buyer_name"],
                    "remarks" => $values["remarks"],
                ]
            );
        }

        $pr_collect = new PoResource($purchase_order);

        return GlobalFunction::save(
            Message::PURCHASE_ORDER_UPDATE,
            $pr_collect
        );
    }

    public function resubmit(Request $request, $id)
    {
        $purchase_order = POTransaction::find($id);

        $user_id = Auth()->user()->id;
        $not_found = POTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $pr_history = PoHistory::where("po_id", $id)->get();

        if ($pr_history->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        foreach ($pr_history as $pr) {
            $pr->update([
                "approved_at" => null,
                "rejected_at" => null,
            ]);
        }

        $orders = $request->order;

        $purchase_order->update([
            "status" => "Pending",
            "rejected_at" => null,
            "reason" => null,
            "layer" => "1",
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = POItems::where("po_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                POItems::where("id", $order_id)->forceDelete();
            }
        }

        foreach ($orders as $index => $values) {
            POItems::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "po_id" => $purchase_order->id,
                    "pr_id" => $purchase_order->pr_number,
                    "item_id" => $values["item_id"],
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "supplier_id" => $values["supplier_id"],
                    "price" => $values["price"],
                    "quantity" => $values["quantity"],
                    "quantity_serve" => $values["quantity_serve"],
                    "total_price" => $values["total_price"],
                    "attachment" => $values["attachment"],
                    "buyer_id" => $values["buyer_id"],
                    "buyer_name" => $values["buyer_name"],
                    "remarks" => $values["remarks"],
                ]
            );
            // POItems::withTrashed()->update([
            //     "po_id" => $purchase_order->id,
            //     "pr_id" => $purchase_order->pr_number,
            //     "supplier_id" => $values["supplier_id"],
            //     "price" => $values["price"],
            //     "total_price" => $values["total_price"],
            // ]);
        }

        $pr_collect = new PoResource($purchase_order);

        return GlobalFunction::save(Message::RESUBMITTED, $pr_collect);
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

    public function resubmit_jo(Request $request, $id)
    {
        $job_order = JOPOTransaction::find($id);

        $user_id = Auth()->user()->id;
        $not_found = JOPOTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return $po_history = JoPoHistory::where("jo_po_id", $id)->get();

        if ($po_history->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        foreach ($po_history as $pr) {
            $pr->update([
                "approved_at" => null,
                "rejected_at" => null,
            ]);
        }

        $orders = $request->order;

        $job_order->update([
            "status" => "Pending",
            "rejected_at" => null,
            "reason" => null,
            "layer" => "1",
        ]);

        $po_settings = POSettings::where("company_id", $job_order->company_id)
            ->get()
            ->first();

        $jo_items = JoPoOrders::where("jo_po_id", $job_order->id)
            ->get()
            ->pluck("total_price")
            ->toArray();

        $sum = array_sum($jo_items);

        $approvers = PoApprovers::where("price_range", "<=", $sum)->get();

        foreach ($approvers as $index) {
            $exists = JoPoHistory::where([
                ["jo_po_id", $job_order->id],
                ["approver_id", $index["approver_id"]],
            ])->exists();

            if (!$exists) {
                JoPoHistory::create([
                    "jo_po_id" => $job_order->id,
                    "approver_id" => $index["approver_id"],
                    "approver_name" => $index["approver_name"],
                    "layer" => $index["layer"],
                ]);
            }
        }

        foreach ($orders as $index => $values) {
            $order_id = $values["id"];
            POItems::where("id", $order_id)
                ->withTrashed()
                ->update([
                    "price" => $values["price"],
                    "total_price" => $values["total_price"],
                ]);
        }

        $jo_collect = new JoPoResource($job_order);

        return GlobalFunction::save(Message::RESUBMITTED, $jo_collect);
    }
}
