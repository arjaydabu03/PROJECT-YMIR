<?php

namespace App\Models;

use App\Filters\RRTransactionFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RRTransaction extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    protected string $default_filters = RRTransactionFilters::class;

    protected $table = "rr_transactions";

    protected $fillable = ["po_id", "pr_id", "received_by", "tagging_id"];

    public function users()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function order()
    {
        return $this->hasMany(PRItems::class, "transaction_id", "id");
    }

    public function po_order()
    {
        return $this->hasMany(POItems::class, "po_id", "po_id");
    }

    public function approver_history()
    {
        return $this->hasMany(PrHistory::class, "pr_id", "id");
    }

    public function po_transaction()
    {
        return $this->belongsTo(
            POTransaction::class,
            "po_id",
            "po_number"
        )->withTrashed();
    }

    public function rr_orders()
    {
        return $this->hasMany(RROrders::class, "rr_id", "id")->withTrashed();
    }
}
