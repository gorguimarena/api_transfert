<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use App\TypeTransaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompteSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vérifier si les données existent déjà
        if (Compte::count() > 0) {
            return;
        }

        $clients = Client::all();

        // Données prédéfinies pour créer des comptes variés
        $comptesData = [
            // Comptes épargne actifs
            [
                'type' => 'epargne',
                'status' => 'active',
                'solde_initial' => 500000,
                'telephone' => '+221771234567',
                'devise' => 'FCFA',
                'motif_blocage' => null,
            ],
            [
                'type' => 'epargne',
                'status' => 'active',
                'solde_initial' => 1000000,
                'telephone' => '+221782345678',
                'devise' => 'EUR',
                'motif_blocage' => null,
            ],
            [
                'type' => 'epargne',
                'status' => 'active',
                'solde_initial' => 2500000,
                'telephone' => '+221763456789',
                'devise' => 'USD',
                'motif_blocage' => null,
            ],
            // Comptes chèque actifs
            [
                'type' => 'cheque',
                'status' => 'active',
                'solde_initial' => 750000,
                'telephone' => '+221774567890',
                'devise' => 'FCFA',
                'motif_blocage' => null,
            ],
            [
                'type' => 'cheque',
                'status' => 'active',
                'solde_initial' => 1500000,
                'telephone' => '+221785678901',
                'devise' => 'EUR',
                'motif_blocage' => null,
            ],
            // Comptes épargne bloqués
            [
                'type' => 'epargne',
                'status' => 'bloque',
                'solde_initial' => 300000,
                'telephone' => '+221776789012',
                'devise' => 'FCFA',
                'motif_blocage' => 'Inactivité de 30+ jours',
            ],
            [
                'type' => 'epargne',
                'status' => 'bloque',
                'solde_initial' => 800000,
                'telephone' => '+221787890123',
                'devise' => 'EUR',
                'motif_blocage' => 'Suspicion de fraude',
            ],
            [
                'type' => 'epargne',
                'status' => 'bloque',
                'solde_initial' => 1200000,
                'telephone' => '+221768901234',
                'devise' => 'USD',
                'motif_blocage' => 'Solde insuffisant',
            ],
        ];

        $compteIndex = 0;

        foreach ($clients as $client) {
            // Créer 2-3 comptes par client avec des données prédéfinies
            $numComptes = min(3, count($comptesData) - $compteIndex);

            for ($i = 0; $i < $numComptes; $i++) {
                if ($compteIndex >= count($comptesData)) {
                    break;
                }

                $compteData = $comptesData[$compteIndex];
                $numero = now()->format('Ymd') . str_pad($compteIndex + 1, 8, '0', STR_PAD_LEFT);

                $compte = Compte::create([
                    'numero_compte' => $numero,
                    'type_compte' => $compteData['type'],
                    'status_compte' => $compteData['status'],
                    'telephone' => $compteData['telephone'],
                    'devise' => $compteData['devise'],
                    'client_id' => $client->id,
                    'is_deleted' => false,
                    'solde_initial' => $compteData['solde_initial'],
                    'motif_blocage' => $compteData['motif_blocage'],
                ]);

                // Créer une transaction de dépôt initial
                Transaction::create([
                    'montant' => $compteData['solde_initial'],
                    'type_transaction' => TypeTransaction::DEPOT->value,
                    'compte_id' => $compte->id,
                ]);

                $compteIndex++;
            }

            // Si on a épuisé les données prédéfinies, créer des comptes aléatoires supplémentaires
            if ($compteIndex >= count($comptesData)) {
                $numComptesAleatoires = rand(1, 2);

                for ($i = 0; $i < $numComptesAleatoires; $i++) {
                    $numero = now()->format('Ymd') . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                    $status = rand(0, 9) < 8 ? 'active' : 'bloque'; // 80% active, 20% bloque
                    $motifBlocage = null;

                    if ($status === 'bloque') {
                        $motifs = [
                            'Inactivité de 30+ jours',
                            'Solde insuffisant',
                            'Suspicion de fraude',
                            'Demande du client',
                            'Décès du titulaire'
                        ];
                        $motifBlocage = $motifs[array_rand($motifs)];
                    }

                    $compte = Compte::create([
                        'numero_compte' => $numero,
                        'type_compte' => ['epargne', 'cheque'][rand(0, 1)],
                        'status_compte' => $status,
                        'telephone' => '+221' . rand(771234567, 789876543),
                        'devise' => ['FCFA', 'EUR', 'USD'][rand(0, 2)],
                        'client_id' => $client->id,
                        'is_deleted' => false,
                        'solde_initial' => rand(50000, 2000000), // Solde initial aléatoire
                        'motif_blocage' => $motifBlocage,
                    ]);

                    // Créer une transaction de dépôt initial
                    $montantInitial = rand(10000, 500000);
                    Transaction::create([
                        'montant' => $montantInitial,
                        'type_transaction' => TypeTransaction::DEPOT->value,
                        'compte_id' => $compte->id,
                    ]);
                }
            }
        }
    }
}
