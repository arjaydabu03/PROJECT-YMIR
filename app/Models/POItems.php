<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POItems extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "po_orders";
    protected $fillable = [
        "po_id",
        "pr_id",
        "item_id",
        "item_code",
        "item_name",
        "supplier_id",
        "uom_id",
        "quantity",
        "quantity_serve",
        "buyer_id",
        "remarks",
    ];
}
