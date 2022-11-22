<?php

namespace App\Http\Resources\V1;

use App\Models\Course;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data =  parent::toArray($request);
        if(isset($data["links"])){
            unset($data["links"]);
        }
        if(isset($data["path"])){
            unset($data["path"]);
        }
        return $data;
    }
}
