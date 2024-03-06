<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class SubUnitFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function department_unit_id($department_unit_id)
    {
        $this->builder->where("department_unit_id", $department_unit_id);
    }
}