<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory;

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
        static::creating(fn($model) => $model->type = 'client');

    }
}
