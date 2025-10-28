<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveComptesJob;
use App\Jobs\DebloquerComptesJob;
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
        $this->info('Exécution des jobs planifiés...');

        // Exécuter le job d'archivage des comptes
        $this->info('Archivage des comptes expirés...');
        ArchiveComptesJob::dispatch();

        // Exécuter le job de déblocage des comptes
        $this->info('Déblocage des comptes expirés...');
        DebloquerComptesJob::dispatch();

        $this->info('Jobs exécutés avec succès!');
    }
}
