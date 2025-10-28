<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Exception;

class SmsService implements ISmsService
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private Client $twilioClient;

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

        // Initialiser le client Twilio
        if ($this->accountSid && $this->authToken) {
            $this->twilioClient = new Client($this->accountSid, $this->authToken);
            Log::info('Twilio client initialized successfully');
        }
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
        Log::info('Tentative d\'envoi SMS via Twilio SDK', [
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

            // Vérifier que le client Twilio est initialisé
            if (!isset($this->twilioClient)) {
                Log::error('Client Twilio non initialisé');
                return false;
            }

            // Formater le numéro de téléphone
            $formattedTo = $this->formatPhoneNumber($to);
            Log::info('Numéro formaté', ['original' => $to, 'formatted' => $formattedTo]);

            // Envoyer le SMS via le SDK Twilio
            $messageInstance = $this->twilioClient->messages->create(
                $formattedTo, // To
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );

            Log::info("SMS envoyé avec succès via Twilio SDK", [
                'to' => $formattedTo,
                'sid' => $messageInstance->sid,
                'status' => $messageInstance->status,
                'direction' => $messageInstance->direction,
                'dateCreated' => $messageInstance->dateCreated->format('Y-m-d H:i:s')
            ]);

            return true;

        } catch (\Twilio\Exceptions\RestException $e) {
            Log::error("Erreur Twilio REST lors de l'envoi du SMS: " . $e->getMessage(), [
                'to' => $to,
                'code' => $e->getCode(),
                'status' => $e->getStatusCode(),
                'moreInfo' => $e->getMoreInfo()
            ]);
            return false;

        } catch (Exception $e) {
            Log::error("Exception lors de l'envoi du SMS via Twilio SDK: " . $e->getMessage(), [
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