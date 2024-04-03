<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PoFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];
}
