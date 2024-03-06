<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class CompanyFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    // public function business_unit_id($company_id)
    // {
    //     $this->builder->whereHas("business_unit", function ($query) use (
    //         $business_unit_id
    //     ) {
    //         $query->where("company_id", $business_unit_id);
    //     });
    // }
}
