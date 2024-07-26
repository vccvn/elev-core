<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NAMEItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            MODEL_PRIMARY_KEY => $this->{MODEL_PRIMARY_KEY},$ELEMENTS
        ];
        return $data;
    }
}
