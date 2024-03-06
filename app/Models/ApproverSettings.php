<?php

namespace App\Models;

use App\Filters\ApproverFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApproverSettings extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    protected string $default_filters = ApproverFilters::class;

    protected $fillable = [
        "module",
        "company_id",
        "business_unit_id",
        "department_id",
        "department_unit_id",
        "sub_unit_id",
        "location_id",
    ];

    public function set_approver()
    {
        return $this->hasMany(SetApprover::class, "approver_settings_id", "id");
    }
}
