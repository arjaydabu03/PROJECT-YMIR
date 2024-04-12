<?php

namespace App\Models;

use App\Filters\ItemFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Items extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    protected string $default_filters = ItemFilters::class;

    protected $fillable = ["name", "code", "uom_id"];
    protected $hidden = ["created_at"];

    public function uom()
    {
        return $this->belongsTo(Uom::class, "uom_id", "id");
    }
}
