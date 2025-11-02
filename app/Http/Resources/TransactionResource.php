<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'compteId' => (string) $this->compte_id,
            'compteDestinationId' => $this->compte_destination_id ? (string) $this->compte_destination_id : null,
            'type' => (string) $this->type_transaction->value,
            'montant' => (float) $this->montant,
            'devise' => (string) ($this->devise ?? 'FCFA'),
            'description' => (string) ($this->description ?? ''),
            'dateTransaction' => $this->created_at->toISOString(),
            'statut' => (string) ($this->statut ?? 'validee')
        ];
    }
}
