<?php

namespace App\Events;

use App\Models\Compte;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, SerializesModels;

    public Compte $compte;
    public string $generatedPassword;
    public string $verificationCode;

    public function __construct(Compte $compte, string $generatedPassword, string $verificationCode)
    {
        $this->compte = $compte;
        $this->generatedPassword = $generatedPassword;
        $this->verificationCode = $verificationCode;
    }
}