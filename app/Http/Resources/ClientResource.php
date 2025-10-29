<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'type' => $this->user->type,
                'created_at' => $this->user->created_at,
                'updated_at' => $this->user->updated_at,
            ],
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'nci' => $this->nci,
            'adresse' => $this->adresse,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}