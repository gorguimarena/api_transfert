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
        $compteIds = $comptes->pluck('id')->toArray();

        foreach ($comptes as $compte) {
            $numTransactions = rand(3, 8);

            for ($i = 0; $i < $numTransactions; $i++) {
                // Générer un type de transaction aléatoire (avec probabilité pour transferts)
                $transactionTypes = [TypeTransaction::DEPOT, TypeTransaction::RETRAIT, TypeTransaction::TRANSFERT];
                $weights = [40, 40, 20]; // 40% dépôts, 40% retraits, 20% transferts

                $rand = rand(1, 100);
                if ($rand <= $weights[0]) {
                    $type = $transactionTypes[0]; // DEPOT
                    $compteDestinationId = null;
                } elseif ($rand <= $weights[0] + $weights[1]) {
                    $type = $transactionTypes[1]; // RETRAIT
                    $compteDestinationId = null;
                } else {
                    $type = $transactionTypes[2]; // TRANSFERT
                    // Sélectionner un compte destination différent
                    $otherComptes = array_filter($compteIds, fn($id) => $id !== $compte->id);
                    $compteDestinationId = $otherComptes[array_rand($otherComptes)];
                }

                Transaction::create([
                    'montant' => rand(100, 10000) / 100,
                    'type_transaction' => $type,
                    'compte_id' => $compte->id,
                    'compte_destination_id' => $compteDestinationId,
                ]);

                // Pour les transferts, créer également la transaction de crédit
                if ($type === TypeTransaction::TRANSFERT) {
                    Transaction::create([
                        'montant' => rand(100, 10000) / 100,
                        'type_transaction' => TypeTransaction::DEPOT,
                        'compte_id' => $compteDestinationId,
                        'compte_destination_id' => null,
                    ]);
                }
            }
        }
    }
}
