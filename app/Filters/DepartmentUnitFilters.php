<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class DepartmentUnitFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function department_id($department_id)
    {
        $this->builder->where("department_id", $department_id);
    }
}
