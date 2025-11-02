<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ArchiveTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $year;
    protected $week;

    /**
     * Create a new job instance.
     */
    public function __construct($year = null, $week = null)
    {
        $this->year = $year ?? Carbon::now()->subWeek()->year;
        $this->week = $week ?? Carbon::now()->subWeek()->weekOfYear;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get transactions from the previous week
        $startOfWeek = Carbon::now()->subWeek()->startOfWeek();
        $endOfWeek = Carbon::now()->subWeek()->endOfWeek();

        $transactions = Transaction::whereBetween('created_at', [$startOfWeek, $endOfWeek])->get();

        // Archive to MongoDB
        $collectionName = "transactions_{$this->year}_W{$this->week}";

        foreach ($transactions as $transaction) {
            DB::connection('mongodb')->collection($collectionName)->insert([
                'id' => $transaction->id,
                'montant' => $transaction->montant,
                'type_transaction' => $transaction->type_transaction,
                'compte_id' => $transaction->compte_id,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
                'archived_at' => Carbon::now(),
            ]);
        }

        // Optionally, delete from PostgreSQL after archiving
        Transaction::whereBetween('created_at', [$startOfWeek, $endOfWeek])->delete();
    }
}