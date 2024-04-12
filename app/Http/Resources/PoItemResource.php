<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PoItemResource extends JsonResource
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
            "pr_id" => $this->pr_id,
            "po_id" => $this->po_id,
            "item" => [
                "id" => $this->item_id,
                "name" => $this->item_name,
                "code" => $this->item_code,
            ],
            "uom" => $this->uom_id,
            "quantity" => $this->quantity,
            "quantity_serve" => $this->quantity_serve,
            "supplier_id" => $this->supplier_id,
            "remarks" => $this->remarks,
        ];
    }
}
