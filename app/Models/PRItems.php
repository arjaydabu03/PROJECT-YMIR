<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PRItems extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "pr_items";
    protected $fillable = [
        "transaction_id",
        "item_id",
        "item_code",
        "item_name",
        "uom_id",
        "quantity",
        "canvas_po",
        "canvas_at",
        "remarks",
    ];
    protected $hidden = ["created_at"];

    public function transaction()
    {
        return $this->belongsTo(PRTransaction::class, "transaction_id", "id");
    }

    public function item()
    {
        return $this->belongsTo(Items::class, "item_id", "id");
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, "uom_id", "id");
    }
}
