<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compte extends Model
{
    /** @use HasFactory<\Database\Factories\CompteFactory> */
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'numero_compte',
        'type_compte',
        'status_compte',
        'telephone',
        'devise',
        'client_id',
        'is_deleted',
        'blocked_at',
        'block_end_date',
        'block_reason',
        'is_archived',
        'archived_at',
        'devise',
        'motif_blocage',
    ];

    public function client() {
        return $this->belongsTo(Client::class);
    }

    public function transactions() {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the calculated balance attribute
     * Solde = Somme des opérations de dépôt - Somme des opérations de retrait
     */
    public function getSoldeAttribute()
    {
        $deposits = $this->transactions()->where('type_transaction', 'depot')->sum('montant');
        $withdrawals = $this->transactions()->where('type_transaction', 'retrait')->sum('montant');

        return $deposits - $withdrawals;
    }

    /**
     * Mutateur pour générer automatiquement un numéro de compte
     */
    protected static function booted()
    {
        static::addGlobalScope('not_deleted', function ($query) {
            $query->where('is_deleted', false);
        });

        static::creating(function ($compte) {
            if (empty($compte->numero_compte)) {
                $compte->numero_compte = self::generateNumeroCompte();
            }
        });
    }

    /**
     * Scope pour les comptes actifs (non supprimés, statut actif, type cheque ou epargne)
     */
    public function scopeActive($query)
    {
        return $query->where('status_compte', 'active')
                    ->whereIn('type_compte', ['cheque', 'epargne']);
    }

    /**
     * Scope pour filtrer les comptes selon le type d'utilisateur
     */
    public function scopeForUser($query)
    {
        $user = auth('api')->user();
        // Si c'est un client, filtrer seulement ses comptes
        if ($user->type === 'client') {
            $client = $user->client;
            if (!$client) {
                // Retourner une requête qui ne retourne rien si le client n'existe pas
                return $query->where('id', null);
            }
            $query->where('client_id', $client->id);
        }
        // Si c'est un admin, pas de filtre supplémentaire (voit tous les comptes)

        return $query;
    }

    /**
     * Génère un numéro de compte unique
     */
    public static function generateNumeroCompte(): string
    {
        do {
            $prefix = now()->format('Ymd');
            $random = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $numero = $prefix . $random;
        } while (self::where('numero_compte', $numero)->exists());

        return $numero;
    }

    /**
     * Génère un numéro de compte unique en vérifiant aussi dans la base Neon
     */
    public static function generateNumeroCompteWithNeonCheck(): string
    {
        do {
            $numero = self::generateNumeroCompte();

            // Vérifier dans la base locale
            $existsLocal = self::where('numero_compte', $numero)->exists();

            // Vérifier dans la base Neon (seulement si la connexion existe)
            $existsNeon = false;
            try {
                $existsNeon = self::on('neon')->where('numero_compte', $numero)->exists();
            } catch (\Exception $e) {
                // Si la connexion Neon n'existe pas ou la table n'existe pas, ignorer
                $existsNeon = false;
            }

        } while ($existsLocal || $existsNeon);

        return $numero;
    }
}
