<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $email;
    protected string $password;
    protected object $client;

    public function __construct(string $email, string $password, object $client)
    {
        $this->email = $email;
        $this->password = $password;
        $this->client = $client;

        // Définir la queue pour les emails
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        // Vérifier la configuration email avant de procéder
        if (config('mail.default') === 'log') {
            Log::info("Configuration email en mode 'log' - simulation d'envoi", [
                'email' => $this->email,
                'client_nom' => $this->client->nom,
                'client_prenom' => $this->client->prenom
            ]);
            return;
        }

        // Utiliser un lock pour éviter les chevauchements
        $lockKey = "email_notification_{$this->email}";
        $lock = Cache::lock($lockKey, 300); // Lock pour 5 minutes

        if (!$lock->get()) {
            Log::warning("Email notification déjà en cours pour {$this->email}");
            return;
        }

        try {
            Log::info("Début de l'envoi de l'email à {$this->email}", [
                'email' => $this->email,
                'client_nom' => $this->client->nom,
                'client_prenom' => $this->client->prenom,
                'mail_mailer' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_username' => config('mail.mailers.smtp.username') ? 'configured' : 'missing',
                'mail_from' => config('mail.from.address')
            ]);

            $subject = "Création de votre compte bancaire - {$this->client->prenom} {$this->client->nom}";
            $body = $this->buildEmailBody();

            Mail::raw($body, function ($message) use ($subject) {
                $message->to($this->email)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("Email envoyé avec succès à {$this->email}");
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'email à {$this->email}: " . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'mail_config' => [
                    'default' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.scheme'),
                    'username' => config('mail.mailers.smtp.username') ? 'configured' : 'missing',
                    'from_address' => config('mail.from.address')
                ]
            ]);
            throw $e; // Re-throw pour que le job soit marqué comme échoué
        } finally {
            $lock->release();
        }
    }

    /**
     * Construit le corps de l'email
     */
    private function buildEmailBody(): string
    {
        return "Bonjour {$this->client->prenom} {$this->client->nom},\n\n" .
               "Votre compte bancaire a été créé avec succès.\n\n" .
               "Voici vos informations de connexion :\n" .
               "Email : {$this->email}\n" .
               "Mot de passe temporaire : {$this->password}\n\n" .
               "Veuillez changer votre mot de passe lors de votre première connexion.\n\n" .
               "Cordialement,\n" .
               "L'équipe bancaire";
    }
}