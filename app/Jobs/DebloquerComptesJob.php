<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DebloquerComptesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Trouver tous les comptes bloqués dont la date de fin de blocage est dépassée
        $comptesToUnlock = Compte::where('status_compte', 'bloque')
            ->whereNotNull('block_end_date')
            ->where('block_end_date', '<=', now())
            ->where('is_archived', false)
            ->get();

        foreach ($comptesToUnlock as $compte) {
            try {
                // Débloquer le compte
                $compte->update([
                    'status_compte' => 'active',
                    'blocked_at' => null,
                    'block_end_date' => null,
                    'block_reason' => null,
                ]);

                Log::info("Compte {$compte->numero_compte} débloqué automatiquement");
            } catch (\Exception $e) {
                Log::error("Erreur lors du déblocage du compte {$compte->numero_compte}: " . $e->getMessage());
            }
        }
    }
}
