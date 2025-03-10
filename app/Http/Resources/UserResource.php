<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'phone' => $this->phone,
            'birthday' => $this->birthday,
            'is_active' => $this->is_active,
            'is_email_verified' => $this->is_email_verified,
            'is_archived' => $this->is_archived,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
