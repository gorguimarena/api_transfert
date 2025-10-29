<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Services\ISmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class SendClientNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Compte $compte;
    protected string $generatedPassword;
    protected string $verificationCode;

    public function __construct(Compte $compte, string $generatedPassword, string $verificationCode)
    {
        $this->compte = $compte;
        $this->generatedPassword = $generatedPassword;
        $this->verificationCode = $verificationCode;
    }

    public function handle(ISmsService $smsService): void
    {
        $compte = $this->compte;
        $client = $compte->client;
        $user = $client->user;

        // Utiliser un lock pour éviter les chevauchements
        $lockKey = "notification_compte_{$compte->id}";
        $lock = Cache::lock($lockKey, 300); // Lock pour 5 minutes

        if (!$lock->get()) {
            Log::warning("Notification déjà en cours pour le compte {$compte->numero_compte}");
            return;
        }

        try {
            Log::info("Début de l'envoi des notifications pour le compte {$compte->numero_compte}", [
                'compte_id' => $compte->id,
                'client_id' => $client->id,
                'user_email' => $user->email,
                'telephone' => $compte->telephone,
                'generatedPassword' => $this->generatedPassword ? 'present' : 'null',
                'verificationCode' => $this->verificationCode ? 'present' : 'null'
            ]);

            // Si c'est un nouveau client (mot de passe généré), envoyer email + SMS
            if (!empty($this->generatedPassword)) {
                // Envoyer l'email avec le mot de passe
                $this->sendEmail($user->email, $this->generatedPassword, $client);
            }

            // Envoyer le SMS avec le code de vérification (toujours envoyé)
            $this->sendSMS($smsService, $compte->telephone, $this->verificationCode);

            Log::info("Notifications envoyées pour le compte {$compte->numero_compte}", [
                'compte_id' => $compte->id,
                'client_id' => $client->id,
                'user_email' => $user->email,
                'telephone' => $compte->telephone
            ]);
        } finally {
            $lock->release();
        }
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
    private function sendSMS(ISmsService $smsService, string $telephone, string $code): void
    {
        Log::info("Tentative d'envoi SMS", [
            'telephone' => $telephone,
            'code' => $code,
            'code_length' => strlen($code)
        ]);

        try {
            $message = "Votre code de vérification est: {$code}";
            Log::info("Message SMS préparé", ['message' => $message]);

            $success = $smsService->sendSms($telephone, $message);

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