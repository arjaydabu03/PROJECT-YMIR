<?php

namespace App\Models;

use App\Filters\PoApproversFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PoApprovers extends Model
{
    use Filterable, HasFactory;

    protected string $default_filters = PoApproversFilters::class;

    protected $fillable = ["approver_id", "approver_name", "layer"];
}
