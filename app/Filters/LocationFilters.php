<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class LocationFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name", "code"];

    protected array $relationSearch = [
        "sub_units" => ["id"],
    ];

    public function vladimir($vladimir)
    {
        $this->builder->when($vladimir == "sync", function ($query) use (
            $vladimir
        ) {
            $query->withTrashed();
        });
    }
    // public function sub_unit_id($sub_unit_id)
    // {
    //     $this->builder->whereHas("sub_units", function ($query) use (
    //         $sub_unit_id
    //     ) {
    //         $query->where("id", $sub_unit_id);
    //     });
    // }
}
