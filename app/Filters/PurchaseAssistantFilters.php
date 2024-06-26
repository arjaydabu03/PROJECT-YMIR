<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PurchaseAssistantFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "pr_number",
        "pr_description",
        "date_needed",
        "user_id",
        "type_id",
        "type_name",
        "business_unit_id",
        "business_unit_name",
        "company_id",
        "company_name",
        "department_id",
        "department_name",
        "department_unit_id",
        "department_unit_name",
        "location_id",
        "location_name",
        "sub_unit_id",
        "sub_unit_name",
        "account_title_id",
        "account_title_name",
        "supplier_id",
        "supplier_name",
        "module_name",
    ];

    public function status($status)
    {
        $this->builder
            ->when($status === "to_po", function ($query) {
                $query
                    ->whereHas("order", function ($query) {
                        $query->where(function ($query) {
                            $query
                                ->whereNull("buyer_id")
                                ->orWhereNull("buyer_name");
                        });
                    })
                    ->with([
                        "order" => function ($query) {
                            $query->whereNull("buyer_id");
                        },
                    ])
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    });
            })
            ->when($status === "pending", function ($query) {
                $query
                    ->with([
                        "po_transaction" => function ($query) {
                            $query
                                ->where("status", "Pending")
                                ->orWhere("status", "For Approval");
                        },
                    ])
                    ->whereHas("order", function ($query) {
                        $query->whereNotNull("buyer_id");
                    })
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at");
            })
            ->when($status === "approved", function ($query) {
                $query
                    ->with([
                        "po_transaction" => function ($query) {
                            $query
                                ->whereNull("rejected_at")
                                ->whereNull("voided_at")
                                ->whereNull("cancelled_at")
                                ->where("status", "For Receiving");
                        },
                    ])
                    // ->whereHas("po_transaction", function ($query) {
                    //     $query
                    //         ->where("status", "For Receiving")
                    //         ->whereNull("rejected_at")
                    //         ->whereNull("voided_at")
                    //         ->whereNull("cancelled_at");
                    // })
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at");
            })
            ->when($status === "rejected", function ($query) {
                // $query->whereHas("po_transaction", function ($query){
                //     $query->whereNotNull("rejected_at")->where("status", "Reject");
                // });
                $query
                    ->with([
                        "po_transaction" => function ($query) {
                            $query->whereNotNull("rejected_at");
                        },
                    ])
                    ->whereHas("order", function ($query) {
                        $query->whereNotNull("buyer_id");
                    })
                    ->whereHas("po_transaction", function ($query) {
                        $query->where("status", "Reject");
                    });
            });
    }
}
