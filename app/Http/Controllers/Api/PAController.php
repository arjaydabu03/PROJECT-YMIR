<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\JOPOTransaction;
use App\Http\Requests\JODisplay;
use App\Http\Requests\PADisplay;
use App\Functions\GlobalFunction;
use App\Models\PurchaseAssistant;
use App\Http\Resources\PoResource;
use App\Http\Resources\PAResources;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\JoPoResource;
use App\Http\Resources\PRPOResource;
use App\Models\JobOrderTransactionPA;
use App\Http\Resources\JobOrderResource;
use App\Http\Resources\PRTransactionResource;

class PAController extends Controller
{
    public function index(PADisplay $request)
    {
        $purchase_request = PurchaseAssistant::with(
            "approver_history",
            "po_transaction",
            "po_transaction.order",
            "po_transaction.approver_history"
        )
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

    public function index_jo(JODisplay $request)
    {
        $user_id = Auth()->user()->id;
        $status = $request->status;
        $job_order_request = JobOrderTransactionPA::with(
            "order",
            "approver_history",
            "jo_po_transaction",
            "jo_po_transaction.jo_approver_history"
        )
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

    public function view_jo(JODisplay $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;
        $job_order_request = JobOrderTransactionPA::with([
            "order" => function ($query) use ($status) {
                $query->when($status === "for_po", function ($query) {
                    $query->whereNull("po_at");
                });
            },
            "approver_history",
            "jo_po_transaction",
            "jo_po_transaction.jo_approver_history",
        ])
            ->where("id", $id)
            ->orderByDesc("updated_at")
            ->get()
            ->first();

        if (!$job_order_request) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        new JobOrderResource($job_order_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $job_order_request
        );
    }

    public function index_jo_po(PRViewRequest $request)
    {
        $user_id = Auth()->user()->id;
        $status = $request->status;
        $job_order_request = JOPOTransaction::with(
            "jo_po_orders",
            "jo_approver_history"
        )
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $job_order_request->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        JoPoResource::collection($job_order_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $job_order_request
        );
    }

    public function view(PADisplay $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_request = PurchaseAssistant::where("id", $id)
            ->with([
                "order" => function ($query) use ($status, $user_id) {
                    if ($status === "to_po") {
                        $query->whereNull("supplier_id")->whereNull("buyer_id");
                    } elseif ($status === "pending") {
                        $query->whereNotNull("supplier_id");
                    }
                },
            ])
            ->orderByDesc("updated_at")
            ->get()
            ->first();

        if (!$purchase_request) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        new PRPOResource($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_request
        );
    }

    public function viewpo(PADisplay $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_request = POTransaction::where("id", $id)
            ->with("order")
            ->orderByDesc("updated_at")
            ->get()
            ->first();

        if (!$purchase_request) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        new PoResource($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_request
        );
    }
}
