<?php

namespace App\Http\Resources;

use App\Http\Resources\RROrdersResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RRResource extends JsonResource
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
            "rr_number" => $this->id,
            "pr_number" => $this->pr_id,
            "po_number" => $this->po_id,
            "tagging_id" => $this->tagging_id,
            "order" => RROrdersResource::collection($this->rr_orders),
        ];
    }
}
