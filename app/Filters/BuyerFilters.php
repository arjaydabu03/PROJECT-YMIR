<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class BuyerFilters extends QueryFilters
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
        $user_id = auth()->user()->id;

        $this->builder
            ->when($status === "po_approved", function ($query) {
                $query
                    ->whereNotNull("approved_at")
                    ->whereHas("po_transaction", function ($query) {
                        $query->where("status", "For Receiving");
                    })
                    ->with("po_transaction", function ($query) {
                        $query->whereNotNull("approved_at");
                    });
            })
            ->when($status === "to_po", function ($query) use ($user_id) {
                $query
                    ->with([
                        "order" => function ($query) use ($user_id) {
                            $query
                                ->where("buyer_id", $user_id)
                                ->whereNull("supplier_id");
                        },
                    ])
                    ->whereHas("order", function ($query) use ($user_id) {
                        $query
                            ->where("buyer_id", $user_id)
                            ->whereNull("supplier_id");
                    })
                    ->where("status", "Approved")
                    ->whereNotNull("approved_at");
            })
            ->when($status === "approved", function ($query) use ($user_id) {
                $query
                    ->where("status", "Approved")
                    ->whereNotNull("approved_at")
                    ->whereHas("po_transaction", function ($query) {
                        $query->where("status", "Approved");
                    });
            })
            ->when($status === "cancel", function ($query) use ($user_id) {
                $query
                    ->whereNotNull("cancelled_at")
                    ->whereNull("approved_at")
                    ->whereHas("order", function ($query) use ($user_id) {
                        $query->where("buyer_id", $user_id);
                    });
            })
            ->when($status === "rejected", function ($query) use ($user_id) {
                $query
                    ->with([
                        "po_transaction" => function ($query) use ($user_id) {
                            return $query->where("status", "Reject");
                        },
                    ])
                    ->whereHas("order", function ($query) use ($user_id) {
                        $query->where("buyer_id", $user_id);
                    })
                    ->whereHas("po_transaction", function ($query) use (
                        $user_id
                    ) {
                        $query
                            ->whereNotNull("rejected_at")
                            ->where("status", "Reject");
                    })
                    ->whereHas("po_transaction.approver_history", function (
                        $query
                    ) {
                        $query->whereNotNull("rejected_at");
                    })
                    ->whereNotNull("approved_at");
            })
            ->when($status === "voided", function ($query) {
                $query->whereNotNull("voided_at");
            })
            ->when($status === "pr_approved", function ($query) {
                $query
                    ->where("status", "Approved")
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at")
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    });
            })
            ->when($status === "pending", function ($query) use ($user_id) {
                $query
                    ->with([
                        "order" => function ($query) use ($user_id) {
                            $query
                                ->whereNotNull("buyer_id")
                                ->where("buyer_id", $user_id);
                        },
                        "po_transaction" => function ($query) use ($user_id) {
                            $query
                                ->whereHas("order", function ($subQuery) use (
                                    $user_id
                                ) {
                                    $subQuery
                                        ->whereNotNull("buyer_id")
                                        ->where("buyer_id", $user_id);
                                })
                                ->where(function ($subQuery) {
                                    $subQuery
                                        ->where("status", "Pending")
                                        ->orWhere("status", "For Approval");
                                });
                        },
                        "po_transaction.order" => function ($query) use (
                            $user_id
                        ) {
                            $query
                                ->whereNotNull("buyer_id")
                                ->where("buyer_id", $user_id);
                        },
                    ])
                    ->where("status", "Approved")
                    ->whereNotNull("approved_at")
                    ->whereHas("po_transaction", function ($query) {
                        $query
                            ->where("status", "Pending")
                            ->orWhere("status", "For Approval");
                    });
            });
    }
}
