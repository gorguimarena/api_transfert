<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\ReviewFailedJobs;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Planifier la revue des jobs échoués tous les jours à minuit
Artisan::command('schedule:review-failed-jobs', function () {
    $this->info('Lancement de la revue des jobs échoués...');
    ReviewFailedJobs::dispatch();
    $this->info('Job de revue des jobs échoués lancé avec succès.');
})->purpose('Lancer manuellement la revue des jobs échoués');
