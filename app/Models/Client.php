<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'nom',
        'prenom',
        'nci',
        'adresse',
        'code_verification',
        'code_utilise',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }

    protected static function booted()
    {
        static::addGlobalScope('client', function ($query) {
            $query->whereHas('user', function ($q) {
                $q->where('type', 'client');
            });
        });
    }
}
