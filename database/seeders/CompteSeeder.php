<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Compte;
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

        foreach ($clients as $client) {
            // Créer 1-2 comptes par client
            $numComptes = rand(1, 2);

            for ($i = 0; $i < $numComptes; $i++) {
                $numero = now()->format('Ymd') . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
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

                Compte::create([
                    'numero_compte' => $numero,
                    'type_compte' => ['epargne', 'cheque'][rand(0, 1)],
                    'status_compte' => $status,
                    'telephone' => '+221' . rand(771234567, 789876543),
                    'client_id' => $client->id,
                    'is_deleted' => false,
                    'devise' => ['FCFA', 'EUR', 'USD'][rand(0, 2)],
                    'solde_initial' => rand(0, 5000000), // Solde initial aléatoire entre 0 et 5M
                    'motif_blocage' => $motifBlocage,
                ]);
            }
        }
    }
}
