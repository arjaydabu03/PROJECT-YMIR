<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RRSyncDisplay extends JsonResource
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
            "pr_number" => $this->pr_number,
            "po_number" => $this->po_number,
            "supplier_id" => [
                "id" => $this->supplier_id,
                "name" => $this->supplier_name,
            ],
            // "rr_transaction" => RRResource::collection(
            //     $this->rr_transaction->select("id")
            // ),
        ];
    }
}
