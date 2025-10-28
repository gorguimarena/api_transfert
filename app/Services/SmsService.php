<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class SmsService implements ISmsService
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.sid');
        $this->authToken = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.from');
    }

    /**
     * Envoyer un SMS à un numéro de téléphone via Twilio
     *
     * @param string $to Numéro de téléphone destinataire
     * @param string $message Contenu du message
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function sendSms(string $to, string $message): bool
    {
        try {
            // Vérifier que les configurations Twilio sont présentes
            if (!$this->accountSid || !$this->authToken || !$this->fromNumber) {
                Log::error('Configuration Twilio manquante');
                return false;
            }

            // URL de l'API Twilio
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";

            // Envoyer la requête POST à Twilio
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post($url, [
                    'From' => $this->fromNumber,
                    'To' => $to,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("SMS envoyé avec succès via Twilio", [
                    'to' => $to,
                    'sid' => $data['sid'] ?? null,
                    'status' => $data['status'] ?? null
                ]);
                return true;
            } else {
                Log::error("Erreur lors de l'envoi du SMS via Twilio", [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

        } catch (Exception $e) {
            Log::error("Exception lors de l'envoi du SMS via Twilio: " . $e->getMessage(), [
                'to' => $to,
                'message' => $message
            ]);
            return false;
        }
    }
}