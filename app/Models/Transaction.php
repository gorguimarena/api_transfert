<?php

namespace App\Models;

use App\TypeTransaction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'montant',
        'type_transaction',
        'compte_id',
    ];

    protected $casts = [
        'type_transaction' => TypeTransaction::class,
        'montant' => 'decimal:2',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }
}
