<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Response\Message;
use App\Models\SetApprover;
use Illuminate\Http\Request;
use App\Models\PRTransaction;
use App\Models\ApproverSettings;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PRTransactionResource;

class ApproverController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $approver_settings = ApproverSettings::where(
            "business_unit_id",
            $user_id->business_unit_id
        )
            ->where("company_id", $user_id->company_id)
            ->where("department_id", $user_id->department_id)
            ->where("department_unit_id", $user_id->department_unit_id)
            ->where("sub_unit_id", $user_id->sub_unit_id)
            ->where("location_id", $user_id->location_id)
            ->get()
            ->first();

        if (empty($approver_settings)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $set_approver = SetApprover::where(
            "approver_settings_id",
            $approver_settings->id
        )
            ->where("approver_id", $user)

            ->get()
            ->first();

        if (empty($set_approver)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        $purchase_request = PRTransaction::with("order")
            ->where("business_unit_id", $user_id->business_unit_id)
            ->where("company_id", $user_id->company_id)
            ->where("department_id", $user_id->department_id)
            ->where("department_unit_id", $user_id->department_unit_id)
            ->where("sub_unit_id", $user_id->sub_unit_id)
            ->where("location_id", $user_id->location_id)
            ->where("layer", $set_approver->layer)
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
    public function approved(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $approver_settings = ApproverSettings::where(
            "business_unit_id",
            $user_id->business_unit_id
        )
            ->where("company_id", $user_id->company_id)
            ->where("department_id", $user_id->department_id)
            ->where("department_unit_id", $user_id->department_unit_id)
            ->where("sub_unit_id", $user_id->sub_unit_id)
            ->where("location_id", $user_id->location_id)
            ->get()
            ->first();

        $approver_settings;

        $set_approver = SetApprover::where(
            "approver_settings_id",
            $approver_settings->id
        )->get();

        $count = count($set_approver);

        $pr_transaction = PRTransaction::find($id);

        if ($count == $pr_transaction->layer) {
            $pr_transaction->update([
                "approved_at" => $date_today,
                "status" => "Approved",
            ]);
            $pr_collect = new PRTransactionResource($pr_transaction);
            return GlobalFunction::responseFunction(
                Message::APPORVED,
                $pr_collect
            );
        }
        $pr_transaction->update([
            "layer" => $pr_transaction->layer + 1,
            "status" => "For approval",
        ]);
        $pr_collect = new PRTransactionResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::APPORVED, $pr_collect);
    }
}
