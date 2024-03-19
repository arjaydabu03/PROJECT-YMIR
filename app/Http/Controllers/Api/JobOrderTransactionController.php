<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\JobItems;
use App\Response\Message;
use App\Models\JobHistory;
use App\Models\SetApprover;
use Illuminate\Http\Request;
use App\Models\ApproverSettings;
use App\Functions\GlobalFunction;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\JobOrderResource;
use App\Http\Requests\JobOrderTransaction\StoreRequest;

class JobOrderTransactionController extends Controller
{
    public function index(PRViewRequest $request)
    {
        $user_id = Auth()->user()->id;
        $status = $request->status;
        $job_order_request = JobOrderTransaction::with("approver_history")

            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $job_order_request->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        JobOrderResource::collection($job_order_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $job_order_request
        );
    }

    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;

        $orders = $request->order;

        $jo_number = JobOrderTransaction::latest()
            ->get()
            ->first();
        $increment = $jo_number ? $jo_number->id + 1 : 1;

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

        $job_order_request = new JobOrderTransaction([
            "jo_number" => $increment,
            "jo_description" => $request["jo_description"],
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
            "module_name" => "Job Order",
            "layer" => "1",
            "description" => $request->description,
        ]);
        $job_order_request->save();

        foreach ($orders as $index => $values) {
            JobItems::create([
                "jo_transaction_id" => $job_order_request->id,
                "description" => $request["order"][$index]["description"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "quantity" => $request["order"][$index]["quantity"],
                "remarks" => $request["order"][$index]["remarks"],
            ]);
        }
        $approver_settings = ApproverSettings::where(
            "company_id",
            $job_order_request->company_id
        )
            ->where("business_unit_id", $job_order_request->business_unit_id)
            ->where("department_id", $job_order_request->department_id)
            ->where(
                "department_unit_id",
                $job_order_request->department_unit_id
            )
            ->where("sub_unit_id", $job_order_request->sub_unit_id)
            ->where("location_id", $job_order_request->location_id)
            ->whereHas("set_approver")
            ->get()
            ->first();

        $approvers = SetApprover::where(
            "approver_settings_id",
            $approver_settings->id
        )->get();

        foreach ($approvers as $index) {
            JobHistory::create([
                "jo_id" => $job_order_request->id,
                "approver_id" => $index["approver_id"],
                "approver_name" => $index["approver_name"],
                "layer" => $index["layer"],
            ]);
        }

        $pr_collect = new JobOrderResource($job_order_request);

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_SAVE,
            $pr_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $job_order_request = JobOrderTransaction::find($id);

        $not_found = JobOrderTransaction::where("id", $id)->exists();

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

        $job_order_request->update([
            "jo_number" => $job_order_request->id,
            "jo_description" => $request["jo_description"],
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
            "module_name" => "Job Order",
            "description" => $request->description,
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = JobItems::where("jo_transaction_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                JobItems::where("id", $order_id)->forceDelete();
            }
        }

        foreach ($orders as $index => $values) {
            JobItems::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "jo_transaction_id" => $job_order_request->id,
                    "description" => $values["description"],
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
                    "remarks" => $values["remarks"],
                ]
            );
        }

        $pr_collect = new JobOrderResource($job_order_request);

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_UPDATE,
            $pr_collect
        );
    }
    public function destroy($id)
    {
        $job_order_request = JobOrderTransaction::where("id", $id)
            ->withTrashed()
            ->get();

        if ($job_order_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $job_order_request = JobOrderTransaction::withTrashed()->find($id);
        $is_active = JobOrderTransaction::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $job_order_request->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $job_order_request->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $job_order_request);
    }
}
