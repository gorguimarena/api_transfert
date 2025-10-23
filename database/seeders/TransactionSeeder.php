<?php

namespace Database\Seeders;

use App\Models\Compte;
use App\Models\Transaction;
use App\TypeTransaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Transaction::count() > 0) {
            return; 
        }

        $comptes = Compte::all();

        foreach ($comptes as $compte) {
            $numTransactions = rand(3, 8);

            for ($i = 0; $i < $numTransactions; $i++) {
                Transaction::create([
                    'montant' => rand(100, 10000) / 100, 
                    'type_transaction' => rand(0, 1) ? TypeTransaction::CREDIT : TypeTransaction::DEBIT,
                    'compte_id' => $compte->id,
                ]);
            }
        }
    }
}
