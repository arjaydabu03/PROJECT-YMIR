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
            // "transaction_id" => $this->transaction_id,
            // "item_code" => $this->item_code,
            "item" => [
                "id" => $this->item_id,
                "name" => $this->item_name,
                "code" => $this->item_code,
            ],
            "uom" => $this->uom_id,
            "po_at" => $this->po_at,
            "purchase_order_id" => $this->purchase_order_id,
            "buyer_id" => $this->buyer_id,
            "buyer_name" => $this->buyer_name,
            "supplier_id" => $this->supplier_id,
            "quantity" => $this->quantity,
            "remarks" => $this->remarks,
            "attachment" => $this->attachment,
            "assets" => $this->asset,
        ];
    }
}
