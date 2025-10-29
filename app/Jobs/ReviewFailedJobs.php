<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReviewFailedJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $maxRetries;
    protected array $retryableJobs;

    public function __construct(int $maxRetries = 3)
    {
        $this->maxRetries = $maxRetries;
        $this->retryableJobs = [
            SendEmailNotificationJob::class,
            SendSmsNotificationJob::class,
        ];
    }

    public function handle(): void
    {
        Log::info("Début de la revue des jobs échoués");

        $failedJobs = $this->getFailedJobs();

        foreach ($failedJobs as $failedJob) {
            $this->processFailedJob($failedJob);
        }

        Log::info("Revue des jobs échoués terminée", [
            'jobs_processed' => count($failedJobs)
        ]);
    }

    /**
     * Récupère les jobs échoués
     */
    private function getFailedJobs(): array
    {
        try {
            return DB::table('failed_jobs')
                ->whereIn('queue', ['default', 'notifications'])
                ->where('failed_at', '>=', now()->subHours(24)) // Dernières 24h
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des jobs échoués: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Traite un job échoué
     */
    private function processFailedJob(object $failedJob): void
    {
        try {
            $payload = json_decode($failedJob->payload, true);
            $jobClass = $payload['displayName'] ?? 'Unknown';

            Log::info("Traitement du job échoué", [
                'job_id' => $failedJob->id,
                'job_class' => $jobClass,
                'failed_at' => $failedJob->failed_at,
                'attempts' => $failedJob->attempts ?? 0
            ]);

            // Vérifier si le job peut être retenté
            if ($this->canRetryJob($jobClass, $failedJob)) {
                $this->retryJob($failedJob);
            } else {
                $this->handlePermanentFailure($failedJob);
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors du traitement du job échoué {$failedJob->id}: " . $e->getMessage());
        }
    }

    /**
     * Vérifie si un job peut être retenté
     */
    private function canRetryJob(string $jobClass, object $failedJob): bool
    {
        // Vérifier si c'est un job retriable
        if (!in_array($jobClass, $this->retryableJobs)) {
            return false;
        }

        // Vérifier le nombre de tentatives
        $attempts = $failedJob->attempts ?? 0;
        if ($attempts >= $this->maxRetries) {
            return false;
        }

        // Vérifier si le job n'est pas trop ancien (plus de 7 jours)
        $failedAt = strtotime($failedJob->failed_at);
        if (time() - $failedAt > 7 * 24 * 60 * 60) {
            return false;
        }

        return true;
    }

    /**
     * Retente un job échoué
     */
    private function retryJob(object $failedJob): void
    {
        try {
            // Remettre le job dans la queue
            DB::table('jobs')->insert([
                'queue' => $failedJob->queue,
                'payload' => $failedJob->payload,
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => time(),
                'created_at' => time(),
            ]);

            // Supprimer de la table des jobs échoués
            DB::table('failed_jobs')->where('id', $failedJob->id)->delete();

            Log::info("Job remis en queue avec succès", [
                'failed_job_id' => $failedJob->id
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la remise en queue du job {$failedJob->id}: " . $e->getMessage());
        }
    }

    /**
     * Gère les échecs permanents
     */
    private function handlePermanentFailure(object $failedJob): void
    {
        try {
            $payload = json_decode($failedJob->payload, true);
            $jobClass = $payload['displayName'] ?? 'Unknown';

            Log::warning("Job marqué comme échec permanent", [
                'job_id' => $failedJob->id,
                'job_class' => $jobClass,
                'failed_at' => $failedJob->failed_at,
                'reason' => 'max_retries_exceeded_or_not_retryable'
            ]);

            // Ici vous pouvez ajouter des actions spécifiques selon le type de job
            // Par exemple: notifier un administrateur, mettre à jour des statistiques, etc.

            // Pour l'instant, on garde le job dans failed_jobs pour analyse
        } catch (\Exception $e) {
            Log::error("Erreur lors du traitement de l'échec permanent du job {$failedJob->id}: " . $e->getMessage());
        }
    }

    /**
     * Nettoie les anciens jobs échoués
     */
    public function cleanupOldFailedJobs(): void
    {
        try {
            $deleted = DB::table('failed_jobs')
                ->where('failed_at', '<', now()->subDays(30))
                ->delete();

            Log::info("Nettoyage des anciens jobs échoués", [
                'jobs_deleted' => $deleted
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors du nettoyage des anciens jobs échoués: " . $e->getMessage());
        }
    }
}