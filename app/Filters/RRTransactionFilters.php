<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class RRTransactionFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "item_id",
        "item_code",
        "item_name",
        "quantity_receive",
        "remaining",
    ];
}
