<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PoFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "pr_number",
        "po_number",
        "po_description",
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
        "module_name",
        "status",
        "layer",
        "description",
        "reason",
        "asset",
        "sgp",
        "f1",
        "f2",
    ];

    public function status($status)
    {
        $user_id = Auth()->user()->id;

        $this->builder
            ->when($status === "pending", function ($query) use ($user_id) {
                $query->where("user_id", $user_id)->where("status", "Pending");
            })
            ->when($status === "cancel", function ($query) use ($user_id) {
                $query
                    ->whereNotNull("cancelled_at")
                    ->whereNull("approved_at")
                    ->where("user_id", $user_id);
            })
            ->when($status === "voided", function ($query) use ($user_id) {
                $query->whereNotNull("voided_at")->where("user_id", $user_id);
            })
            ->when($status === "rejected", function ($query) use ($user_id) {
                $query->whereNotNull("rejected_at")->where("user_id", $user_id);
            })
            ->when($status === "po_approved", function ($query) use ($user_id) {
                $query
                    ->where("status", "For Receiving")
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at")
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    })
                    ->whereHas("order", function ($query) use ($user_id) {
                        $query->where("buyer_id", $user_id);
                    });
            })
            ->when($status === "for_receiving", function ($query) {
                $query
                    ->with([
                        "order" => function ($query) {
                            $query->whereColumn(
                                "quantity",
                                "<>",
                                "quantity_serve"
                            );
                        },
                        // "rr_transaction.rr_orders" => function ($query) {
                        //     $query->where("remaining", "!=", 0);
                        // },
                    ])
                    ->where("status", "For Receiving")
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at")
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    })
                    ->whereHas("order", function ($query) {
                        $query->whereColumn("quantity", "<>", "quantity_serve");
                    });
            });
    }
}
