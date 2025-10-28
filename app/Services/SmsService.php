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

        Log::info('SmsService initialized', [
            'accountSid' => $this->accountSid ? 'configured' : 'missing',
            'authToken' => $this->authToken ? 'configured' : 'missing',
            'fromNumber' => $this->fromNumber ? 'configured' : 'missing'
        ]);
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
        Log::info('Tentative d\'envoi SMS', [
            'to' => $to,
            'message_length' => strlen($message),
            'accountSid' => substr($this->accountSid, 0, 10) . '...',
            'fromNumber' => $this->fromNumber
        ]);

        try {
            // Vérifier que les configurations Twilio sont présentes
            if (!$this->accountSid || !$this->authToken || !$this->fromNumber) {
                Log::error('Configuration Twilio manquante', [
                    'accountSid' => $this->accountSid ? 'set' : 'missing',
                    'authToken' => $this->authToken ? 'set' : 'missing',
                    'fromNumber' => $this->fromNumber ? 'set' : 'missing'
                ]);
                return false;
            }

            // Formater le numéro de téléphone
            $formattedTo = $this->formatPhoneNumber($to);
            Log::info('Numéro formaté', ['original' => $to, 'formatted' => $formattedTo]);

            // URL de l'API Twilio
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
            Log::info('URL Twilio', ['url' => $url]);

            // Préparer les données
            $postData = [
                'From' => $this->fromNumber,
                'To' => $formattedTo,
                'Body' => $message,
            ];
            Log::info('Données POST', ['data' => $postData]);

            // Envoyer la requête POST à Twilio
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post($url, $postData);

            Log::info('Réponse Twilio reçue', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'headers' => $response->headers()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("SMS envoyé avec succès via Twilio", [
                    'to' => $formattedTo,
                    'sid' => $data['sid'] ?? null,
                    'status' => $data['status'] ?? null,
                    'full_response' => $data
                ]);
                return true;
            } else {
                $errorData = $response->json();
                Log::error("Erreur lors de l'envoi du SMS via Twilio", [
                    'to' => $formattedTo,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'error_data' => $errorData,
                    'headers' => $response->headers()
                ]);
                return false;
            }

        } catch (Exception $e) {
            Log::error("Exception lors de l'envoi du SMS via Twilio: " . $e->getMessage(), [
                'to' => $to,
                'message' => $message,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Formater le numéro de téléphone pour Twilio
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Supprimer tous les espaces et caractères non numériques sauf +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Si le numéro ne commence pas par +, ajouter le préfixe international
        if (!str_starts_with($phone, '+')) {
            // Pour le Sénégal, ajouter +221 si nécessaire
            if (str_starts_with($phone, '221')) {
                $phone = '+' . $phone;
            } elseif (str_starts_with($phone, '77') || str_starts_with($phone, '78') || str_starts_with($phone, '76') || str_starts_with($phone, '70')) {
                $phone = '+221' . $phone;
            } else {
                $phone = '+' . $phone;
            }
        }

        return $phone;
    }
}