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
        'client_id',
        'is_deleted',
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
     * Génère un numéro de compte unique
     */
    private static function generateNumeroCompte(): string
    {
        do {
            $prefix = now()->format('Ymd');
            $random = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $numero = $prefix . $random;
        } while (self::where('numero_compte', $numero)->exists());

        return $numero;
    }
}
