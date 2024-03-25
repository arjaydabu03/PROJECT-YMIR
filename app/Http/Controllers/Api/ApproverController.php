<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\PrHistory;
use App\Response\Message;
use App\Models\JobHistory;
use App\Models\SetApprover;
use Illuminate\Http\Request;
use App\Models\PRTransaction;
use App\Models\ApproverSettings;
use App\Functions\GlobalFunction;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use App\Http\Resources\JobOrderResource;
use App\Http\Requests\Approver\RejectRequest;
use App\Http\Resources\PRTransactionResource;

class ApproverController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth()->user()->id;

        $status = $request->status;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $pr_id = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("pr_id");
        $layer = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        if (empty($pr_id) || empty($layer)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request = PRTransaction::with("order", "approver_history")
            ->when($status == "pending", function ($query) use (
                $pr_id,
                $layer
            ) {
                $query
                    ->whereIn("id", $pr_id)
                    ->whereIn("layer", $layer)
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at");
            })
            ->when($status == "rejected", function ($query) use (
                $pr_id,
                $layer
            ) {
                $query
                    ->whereIn("id", $pr_id)
                    ->whereIn("layer", $layer)
                    ->whereNull("voided_at")
                    ->whereNotNull("rejected_at");
            })

            ->when($status == "approved", function ($query) use (
                $pr_id,
                $layer,
                $user_id
            ) {
                $query
                    ->whereIn("id", $pr_id)
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
        $purchase_collect = PRTransactionResource::collection(
            $purchase_request
        );

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_collect
        );
    }

    public function job_order(Request $request)
    {
        $user = Auth()->user()->id;

        $status = $request->status;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $jo_approvers = JobHistory::where("approver_id", $user)
            ->get()
            ->first();

        if (empty($jo_approvers)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $jo_approvers = JobOrderTransaction::with("order")
            ->when($status == "pending", function ($query) use ($jo_approvers) {
                $query
                    ->where("id", $jo_approvers->jo_id)
                    ->where("layer", $jo_approvers->layer)
                    ->whereNull("voided_at")
                    ->whereNull("rejected_at");
            })
            ->when($status == "rejected", function ($query) use (
                $jo_approvers
            ) {
                $query
                    ->where("id", $jo_approvers->jo_id)
                    ->where("layer", $jo_approvers->layer)
                    ->whereNull("voided_at")
                    ->whereNotNull("rejected_at");
            })
            ->useFilters()
            ->dynamicPaginate();

        if ($jo_approvers->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        JobOrderResource::collection($jo_approvers);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $jo_approvers
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

        $set_approver = PrHistory::where("pr_id", $id)->get();

        if (empty($set_approver)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $approved_history = PrHistory::where("pr_id", $id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "approved_at" => $date_today,
            ]);

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

    public function cancelled(Request $request, $id)
    {
        $user = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $pr_transaction = PRTransaction::find($id)
            ->where("user_id", $user)
            ->get()
            ->first();

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
            "status" => "Voided",
            "voided_at" => $date_today,
        ]);

        $pr_collect = new PRTransactionResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::VOIDED, $pr_collect);
    }
    public function rejected(RejectRequest $request, $id)
    {
        $user = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $pr_transaction = PRTransaction::find($id);

        $pr_transaction->update([
            "status" => "Reject",
            "reason" => $request["reason"],
            "rejected_at" => $date_today,
        ]);

        $to_reject = PrHistory::where("pr_id", $pr_transaction->id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "rejected_at" => $date_today,
            ]);

        $pr_collect = new PRTransactionResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::REJECTED, $pr_collect);
    }
}
