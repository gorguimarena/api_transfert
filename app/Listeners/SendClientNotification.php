<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Services\ISmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendClientNotification implements ShouldQueue
{
    protected ISmsService $smsService;

    public function __construct(ISmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Handle the event.
     */
    public function handle(CompteCreated $event): void
    {
        $compte = $event->compte;
        $client = $compte->client;
        $user = $client->user;

        Log::info("Début de l'envoi des notifications pour le compte {$compte->numero_compte}", [
            'compte_id' => $compte->id,
            'client_id' => $client->id,
            'user_email' => $user->email,
            'telephone' => $compte->telephone,
            'generatedPassword' => $event->generatedPassword ? 'present' : 'null',
            'verificationCode' => $event->verificationCode ? 'present' : 'null'
        ]);

        // Envoyer l'email avec le mot de passe
        $this->sendEmail($user->email, $event->generatedPassword, $client);

        // Envoyer le SMS avec le code de vérification
        $this->sendSMS($compte->telephone, $event->verificationCode);

        Log::info("Notifications envoyées pour le compte {$compte->numero_compte}", [
            'compte_id' => $compte->id,
            'client_id' => $client->id,
            'user_email' => $user->email,
            'telephone' => $compte->telephone
        ]);
    }


    /**
     * Envoyer l'email d'authentification
     */
    private function sendEmail(string $email, string $password, $client): void
    {
        try {
            // Ici vous pouvez utiliser un template d'email ou une classe Mailable
            Mail::raw(
                "Bonjour {$client->prenom} {$client->nom},\n\n" .
                "Votre compte bancaire a été créé avec succès.\n\n" .
                "Voici vos informations de connexion :\n" .
                "Email : {$email}\n" .
                "Mot de passe temporaire : {$password}\n\n" .
                "Veuillez changer votre mot de passe lors de votre première connexion.\n\n" .
                "Cordialement,\n" .
                "L'équipe bancaire",
                function ($message) use ($email, $client) {
                    $message->to($email)
                            ->subject('Création de votre compte bancaire - ' . $client->prenom . ' ' . $client->nom);
                }
            );

            Log::info("Email envoyé avec succès à {$email}");
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'email à {$email}: " . $e->getMessage());
        }
    }

    /**
     * Envoyer le SMS avec le code de vérification
     */
    private function sendSMS(string $telephone, string $code): void
    {
        Log::info("Tentative d'envoi SMS", [
            'telephone' => $telephone,
            'code' => $code,
            'code_length' => strlen($code)
        ]);

        try {
            $message = "Votre code de vérification est: {$code}";
            Log::info("Message SMS préparé", ['message' => $message]);

            $success = $this->smsService->sendSms($telephone, $message);

            if ($success) {
                Log::info("SMS envoyé avec succès au {$telephone} avec le code: {$code}");
            } else {
                Log::error("Erreur lors de l'envoi du SMS au {$telephone}");
            }

        } catch (\Exception $e) {
            Log::error("Exception lors de l'envoi du SMS au {$telephone}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}