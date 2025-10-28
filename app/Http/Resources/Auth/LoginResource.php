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
            'access_token' => $this->tokenData['access_token'] ?? null,
            'expires_in' => $this->tokenData['expires_in'] ?? null,
            'refresh_token' => $this->tokenData['refresh_token'] ?? null,
        ];
    }
}
