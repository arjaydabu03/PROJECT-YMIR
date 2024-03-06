<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubUnitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "code" => $this->code,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
            "locations" => [
                "id" => $this->location->id,
                "name" => $this->location->name,
                "code" => $this->location->code,
                "updated_at" => $this->location->updated_at,
                "deleted_at" => $this->location->deleted_at,
            ],
        ];
    }
}
