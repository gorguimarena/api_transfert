<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveComptesJob;
use App\Jobs\DebloquerComptesJob;
use App\Jobs\RestoreComptesJob;
use Illuminate\Console\Command;

class ScheduleJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:run-scheduled';

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

        $this->info('Tous les jobs quotidiens exécutés avec succès!');
    }
}
