<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveComptesJob;
use App\Jobs\ArchiveTransactionsJob;
use App\Jobs\DebloquerComptesJob;
use App\Jobs\RestoreComptesJob;
use App\Jobs\ReviewFailedJobs;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ScheduleJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:run-scheduled {--weekly : Run weekly jobs instead of daily}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exécute les jobs planifiés pour l\'archivage et le déblocage des comptes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('weekly')) {
            $this->runWeeklyJobs();
        } else {
            $this->runDailyJobs();
        }
    }

    private function runDailyJobs()
    {
        $this->info('Exécution des jobs planifiés quotidiens...');

        // Exécuter le job d'archivage des comptes (comptes bloqués depuis plus de 30 jours)
        $this->info('Archivage des comptes bloqués depuis plus de 30 jours...');
        ArchiveComptesJob::dispatch();

        // Exécuter le job de déblocage des comptes (comptes dont la période de blocage est terminée)
        $this->info('Déblocage automatique des comptes dont la période de blocage est terminée...');
        DebloquerComptesJob::dispatch();

        // Exécuter le job de restauration des comptes depuis Neon
        $this->info('Restauration des comptes archivés dont la période de blocage est terminée...');
        RestoreComptesJob::dispatch();

        // Exécuter le job de revue des jobs échoués
        $this->info('Revue et traitement des jobs de notification échoués...');
        ReviewFailedJobs::dispatch();

        $this->info('Tous les jobs quotidiens exécutés avec succès!');
    }

    private function runWeeklyJobs()
    {
        $this->info('Exécution des jobs planifiés hebdomadaires...');

        // Exécuter le job d'archivage des transactions de la semaine précédente vers MongoDB
        $this->info('Archivage des transactions de la semaine précédente vers MongoDB...');
        ArchiveTransactionsJob::dispatch();

        $this->info('Tous les jobs hebdomadaires exécutés avec succès!');
    }
}
