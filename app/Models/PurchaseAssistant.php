<?php

namespace App\Models;

use App\Models\POTransaction;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use App\Filters\PurchaseAssistantFilters;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseAssistant extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = PurchaseAssistantFilters::class;

    protected $table = "pr_transactions";

    protected $fillable = [
        "pr_number",
        "pr_description",
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
        "supplier_id",
        "supplier_name",
        "module_name",
        "transaction_number",
        "status",
        "layer",
        "description",
        "reason",
        "asset",
        "sgp",
        "f1",
        "f2",
        "approved_at",
        "rejected_at",
        "voided_at",
        "cancelled_at",

        "approver_id",
    ];

    public function users()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function order()
    {
        return $this->hasMany(PRItems::class, "transaction_id", "id");
    }

    public function po_transaction()
    {
        return $this->hasMany(
            POTransaction::class,
            "pr_number",
            "pr_number"
        )->withTrashed();
    }

    public function approver_history()
    {
        return $this->hasMany(PrHistory::class, "pr_id", "id");
    }

    public function assets()
    {
        return $this->belongsTo(Assets::class, "asset", "id");
    }
}
