<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetApprover extends Model
{
    use HasFactory;

    protected $fillable = [
        "approver_settings_id",
        "department_id",
        "department_unit_id",
        "sub_unit_id",
        "location_id",
        "approver_id",
        "approver_name",
        "layer",
    ];
}
