<?php

namespace App\Jobs;

use App\Services\ISmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendSmsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $telephone;
    protected string $code;

    public function __construct(string $telephone, string $code)
    {
        $this->telephone = $telephone;
        $this->code = $code;

        // Définir la queue pour les SMS
        $this->onQueue('sms');
    }

    public function handle(ISmsService $smsService): void
    {
        // Vérifier si les credentials Twilio sont configurés
        if (!config('services.twilio.sid') || !config('services.twilio.token') || !config('services.twilio.from')) {
            Log::warning("Configuration Twilio incomplète - simulation d'envoi SMS", [
                'telephone' => $this->telephone,
                'code' => $this->code
            ]);
            return;
        }

        // Utiliser un lock pour éviter les chevauchements
        $lockKey = "sms_notification_{$this->telephone}";
        $lock = Cache::lock($lockKey, 300); // Lock pour 5 minutes

        if (!$lock->get()) {
            Log::warning("SMS notification déjà en cours pour {$this->telephone}");
            return;
        }

        try {
            Log::info("Début de l'envoi du SMS", [
                'telephone' => $this->telephone,
                'code' => $this->code,
                'code_length' => strlen($this->code),
                'twilio_sid' => config('services.twilio.sid') ? 'configured' : 'missing',
                'twilio_from' => config('services.twilio.from')
            ]);

            $message = $this->buildSmsMessage();
            Log::info("Message SMS préparé", ['message' => $message]);

            $success = $smsService->sendSms($this->telephone, $message);

            if ($success) {
                Log::info("SMS envoyé avec succès au {$this->telephone}", [
                    'telephone' => $this->telephone,
                    'code' => $this->code
                ]);
            } else {
                Log::error("Échec de l'envoi du SMS au {$this->telephone}", [
                    'telephone' => $this->telephone,
                    'code' => $this->code
                ]);
                throw new \Exception("Service SMS a retourné false");
            }
        } catch (\Exception $e) {
            Log::error("Exception lors de l'envoi du SMS au {$this->telephone}: " . $e->getMessage(), [
                'telephone' => $this->telephone,
                'code' => $this->code,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'twilio_config' => [
                    'sid' => config('services.twilio.sid') ? 'configured' : 'missing',
                    'token' => config('services.twilio.token') ? 'configured' : 'missing',
                    'from' => config('services.twilio.from')
                ]
            ]);
            throw $e; // Re-throw pour que le job soit marqué comme échoué
        } finally {
            $lock->release();
        }
    }

    /**
     * Construit le message SMS
     */
    private function buildSmsMessage(): string
    {
        return "Votre code de vérification est: {$this->code}";
    }
}