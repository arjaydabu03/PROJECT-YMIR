<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JoPoHistory extends Model
{
    use HasFactory;
    protected $table = "jo_po_history";
    protected $fillable = [
        "jo_po_id",
        "approver_id",
        "approver_name",
        "approved_at",
        "rejected_at",
        "layer",
    ];
}
