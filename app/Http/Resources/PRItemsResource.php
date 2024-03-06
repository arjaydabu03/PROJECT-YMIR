<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PRItemsResource extends JsonResource
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
            // "id"  =>$this->id,
            "transaction_id" => $this->transaction_id,
            "item" => [
                "id" => $this->item_id,
                "name" => $this->item_name,
                "code" => $this->item_code,
            ],
            "uom" => $this->uom_id,
            "quantity" => $this->quantity,
            "canvas" => $this->canvas,
            "remarks" => $this->remarks,
        ];
    }
}
