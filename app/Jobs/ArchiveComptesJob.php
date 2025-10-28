<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArchiveComptesJob implements ShouldQueue
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
        // Trouver tous les comptes bloqués dont la date de début de blocage est dépassée
        $comptesToArchive = Compte::where('status_compte', 'bloque')
            ->whereNotNull('blocked_at')
            ->where('blocked_at', '<=', now())
            ->where('is_archived', false)
            ->get();

        foreach ($comptesToArchive as $compte) {
            DB::beginTransaction();
            try {
                // Archiver le compte dans la base Neon
                $this->archiveToNeon($compte);

                // Supprimer le compte de la base locale
                $compte->delete();

                DB::commit();

                Log::info("Compte {$compte->numero_compte} archivé avec succès");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erreur lors de l'archivage du compte {$compte->numero_compte}: " . $e->getMessage());
            }
        }
    }

    /**
     * Archive le compte et ses transactions dans la base Neon
     */
    private function archiveToNeon(Compte $compte): void
    {
        // Utiliser la connexion Neon
        $compte->setConnection('neon');

        // Créer le compte dans Neon
        $neonCompte = Compte::on('neon')->create([
            'numero_compte' => $compte->numero_compte,
            'type_compte' => $compte->type_compte,
            'status_compte' => $compte->status_compte,
            'telephone' => $compte->telephone,
            'client_id' => $compte->client_id,
            'devise' => $compte->devise ?? 'FCFA',
            'solde_initial' => $compte->solde_initial,
            'is_deleted' => false,
            'blocked_at' => $compte->blocked_at,
            'block_end_date' => $compte->block_end_date,
            'block_reason' => $compte->block_reason,
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        // Archiver les transactions associées
        $transactions = $compte->transactions()->get();
        foreach ($transactions as $transaction) {
            Transaction::on('neon')->create([
                'compte_id' => $neonCompte->id,
                'type_transaction' => $transaction->type_transaction,
                'montant' => $transaction->montant,
                'description' => $transaction->description,
                'date_transaction' => $transaction->date_transaction,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
            ]);
        }

        // Remettre la connexion par défaut
        $compte->setConnection('pgsql');
    }
}
