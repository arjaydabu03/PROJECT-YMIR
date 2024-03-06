<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class ItemFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];
}
