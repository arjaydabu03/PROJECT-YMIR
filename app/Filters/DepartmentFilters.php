<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class DepartmentFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function business_unit_id($business_unit_id)
    {
        $this->builder->where("business_unit_id", $business_unit_id);
    }
}
