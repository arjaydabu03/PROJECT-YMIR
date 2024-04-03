<?php

namespace App\Models;

use App\Filters\UserFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Filterable, SoftDeletes;

    protected $fillable = [
        "prefix_id",
        "id_number",
        "first_name",
        "middle_name",
        "last_name",
        "suffix",
        "position_name",
        "mobile_no",
        "company_id",
        "business_unit_id",
        "department_id",
        "department_unit_id",
        "sub_unit_id",
        "location_id",
        "warehouse_id",
        "username",
        "password",
        "role_id",
    ];

    protected $hidden = [
        "password",
        "created_at",
        "company_id",
        "business_unit_id",
        "department_id",
        "department_unit_id",
        "sub_unit_id",
        "location_id",
        "warehouse_id",
    ];

    protected string $default_filters = UserFilters::class;

    public function role()
    {
        return $this->belongsTo(Role::class, "role_id", "id")->withTrashed();
    }

    public function company()
    {
        return $this->belongsTo(
            Company::class,
            "company_id",
            "id"
        )->withTrashed();
    }

    public function business_unit()
    {
        return $this->belongsTo(
            BusinessUnit::class,
            "business_unit_id",
            "id"
        )->withTrashed();
    }

    public function department()
    {
        return $this->belongsTo(
            Department::class,
            "department_id",
            "id"
        )->withTrashed();
    }

    public function department_unit()
    {
        return $this->belongsTo(
            DepartmentUnit::class,
            "department_unit_id",
            "id"
        )->withTrashed();
    }

    public function sub_unit()
    {
        return $this->belongsTo(
            SubUnit::class,
            "sub_unit_id",
            "id"
        )->withTrashed();
    }

    public function location()
    {
        return $this->belongsTo(
            Location::class,
            "location_id",
            "id"
        )->withTrashed();
    }

    public function warehouse()
    {
        return $this->belongsTo(
            Warehouse::class,
            "warehouse_id",
            "id"
        )->withTrashed();
    }
}
