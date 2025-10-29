<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestoreComptesJob implements ShouldQueue
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
        Log::info('Vérification des comptes à restaurer depuis Neon...');

        try {
            // Trouver tous les comptes archivés dans Neon dont la date de fin de blocage est dépassée
            $comptesToRestore = Compte::on('neon')
                ->where('is_archived', true)
                ->whereNotNull('block_end_date')
                ->where('block_end_date', '<=', now())
                ->get();

            Log::info("Nombre de comptes à restaurer : " . $comptesToRestore->count());

            foreach ($comptesToRestore as $neonCompte) {
                DB::beginTransaction();
                try {
                    // Restaurer le compte dans la base locale
                    $this->restoreFromNeon($neonCompte);

                    // Supprimer le compte de Neon après restauration
                    $neonCompte->delete();

                    DB::commit();

                    Log::info("Compte {$neonCompte->numero_compte} restauré avec succès");
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Erreur lors de la restauration du compte {$neonCompte->numero_compte}: " . $e->getMessage());
                }
            }

            Log::info('Restauration des comptes terminée');

        } catch (\Exception $e) {
            Log::error("Erreur générale lors de la restauration : " . $e->getMessage());
        }
    }

    /**
     * Restaure le compte et ses transactions depuis la base Neon vers la base locale
     */
    private function restoreFromNeon(Compte $neonCompte): void
    {
        // Créer le compte dans la base locale
        $localCompte = Compte::create([
            'numero_compte' => $neonCompte->numero_compte,
            'type_compte' => $neonCompte->type_compte,
            'status_compte' => 'active', // Restaurer comme actif
            'telephone' => $neonCompte->telephone,
            'client_id' => $neonCompte->client_id,
            'devise' => $neonCompte->devise ?? 'FCFA',
            'solde_initial' => $neonCompte->solde_initial,
            'is_deleted' => false,
            'blocked_at' => null, // Réinitialiser
            'block_end_date' => null, // Réinitialiser
            'block_reason' => null, // Réinitialiser
            'is_archived' => false, // Marquer comme non archivé
        ]);

        // Restaurer les transactions associées
        $neonTransactions = Transaction::on('neon')
            ->where('compte_id', $neonCompte->id)
            ->get();

        foreach ($neonTransactions as $neonTransaction) {
            Transaction::create([
                'compte_id' => $localCompte->id,
                'type_transaction' => $neonTransaction->type_transaction,
                'montant' => $neonTransaction->montant,
                'description' => $neonTransaction->description ?? 'Transaction restaurée',
                'date_transaction' => $neonTransaction->date_transaction ?? $neonTransaction->created_at,
                'created_at' => $neonTransaction->created_at,
                'updated_at' => $neonTransaction->updated_at,
            ]);
        }
    }
}