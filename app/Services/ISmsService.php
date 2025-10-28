<?php

namespace App\Services;

interface ISmsService
{
    /**
     * Envoyer un SMS à un numéro de téléphone
     *
     * @param string $to Numéro de téléphone destinataire
     * @param string $message Contenu du message
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function sendSms(string $to, string $message): bool;
}