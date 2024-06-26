<?php

namespace App\Models;

use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use App\Filters\JobOrderTransactionFiltersPA;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobOrderTransactionPA extends Model
{
    use Filterable, SoftDeletes, HasFactory;

    protected string $default_filters = JobOrderTransactionFiltersPA::class;

    protected $table = "jo_transactions";

    protected $fillable = [
        "jo_number",
        "jo_description",
        "date_needed",
        "user_id",
        "type_id",
        "type_name",
        "business_unit_id",
        "business_unit_name",
        "company_id",
        "company_name",
        "department_id",
        "department_name",
        "department_unit_id",
        "department_unit_name",
        "location_id",
        "location_name",
        "sub_unit_id",
        "sub_unit_name",
        "account_title_id",
        "account_title_name",
        "asset",
        "module_name",
        "total_price",
        "status",
        "layer",
        "description",
        "reason",
        "approved_at",
        "rejected_at",
        "voided_at",
        "cancelled_at",
        "approver_id",
        "for_po_only",
        "for_po_only_id",
    ];

    public function users()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function order()
    {
        return $this->hasMany(JobItems::class, "jo_transaction_id", "id");
    }

    public function approver_history()
    {
        return $this->hasMany(JobHistory::class, "jo_id", "id");
    }

    public function jo_po_transaction()
    {
        return $this->hasMany(JOPOTransaction::class, "jo_number", "jo_number");
    }

    public function jo_approver_history()
    {
        return $this->hasMany(JoPoHistory::class, "jo_po_id", "id");
    }

    public function assets()
    {
        return $this->belongsTo(Assets::class, "asset", "id");
    }
}
