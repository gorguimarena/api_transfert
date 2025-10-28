<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MigrateNeonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:neon {--path= : The path to the migrations files to be executed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations on the Neon database connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running migrations on Neon database...');

        try {
            // Set the database connection to neon
            config(['database.default' => 'neon']);

            // Run migrations with the specified path or default to neon_migre
            $path = $this->option('path') ?: 'database/migrations/neon_migre';

            $exitCode = Artisan::call('migrate', [
                '--database' => 'neon',
                '--path' => $path,
                '--force' => true,
            ]);

            if ($exitCode === 0) {
                $this->info('Migrations completed successfully on Neon database.');
            } else {
                $this->error('Migration failed. Exit code: ' . $exitCode);
                $this->error(Artisan::output());
            }

            // Reset the default connection back to pgsql
            config(['database.default' => 'pgsql']);

        } catch (\Exception $e) {
            $this->error('Error running migrations: ' . $e->getMessage());
            config(['database.default' => 'pgsql']);
        }
    }
}