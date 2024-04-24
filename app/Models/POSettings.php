<?php

namespace App\Models;

use App\Filters\PoApproversFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POSettings extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = PoApproversFilters::class;

    protected $table = "po_settings";

    protected $fillable = ["module", "company_id", "company_name"];

    public function set_approver()
    {
        return $this->hasMany(PoApprovers::class, "po_settings_id", "id");
    }
}
