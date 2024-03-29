<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetApprover extends Model
{
    use HasFactory;

    protected $fillable = [
        "approver_settings_id",
        "approver_id",
        "approver_name",
        "layer",
    ];
}
