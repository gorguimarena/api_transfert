<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => (string) $this->id,
            'numeroCompte' => (string) $this->numero_compte,
            'titulaire' => (string) $this->client->user->name,
            'type' => (string) $this->type_compte,
            'solde' => (float) $this->solde,
            'devise' => (string) ($this->devise ?? 'FCFA'),
            'dateCreation' => $this->created_at->toISOString(),
            'statut' => (string) $this->status_compte,
            'metadata' => [
                'derniereModification' => $this->updated_at->toISOString(),
                'version' => (int) 1
            ]
        ];

        if ($this->status_compte === 'bloque' && $this->motif_blocage) {
            $data['motifBlocage'] = (string) $this->motif_blocage;
        }

        return $data;
    }
}
