<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    private $tokenData;

    public function __construct($resource, $tokenData = null)
    {
        parent::__construct($resource);
        $this->tokenData = $tokenData;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'id' => $this->resource->id,
                'name' => $this->resource->name,
                'email' => $this->resource->email,
                'type' => $this->resource->type,
                'created_at' => $this->resource->created_at,
                'updated_at' => $this->resource->updated_at,
            ],
            'access_token' => $this->tokenData['access_token'] ?? null,
            'token_type' => $this->tokenData['token_type'] ?? 'Bearer',
            'expires_in' => $this->tokenData['expires_in'] ?? null,
            'scope' => $this->resource->type === 'admin' ? 'admin' : 'client',
        ];
    }
}
