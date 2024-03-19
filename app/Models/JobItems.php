<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobItems extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "jo_items";
    protected $fillable = [
        "jo_transaction_id",
        "description",
        "uom_id",
        "quantity",
        "remarks",
    ];
    protected $hidden = ["created_at"];

    public function transaction()
    {
        return $this->belongsTo(JobOrderTransaction::class, "jo_id", "id");
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, "uom_id", "id");
    }
}
