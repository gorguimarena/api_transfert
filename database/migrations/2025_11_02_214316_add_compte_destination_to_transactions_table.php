<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Ajouter la colonne compte_destination_id pour les transferts
            $table->uuid('compte_destination_id')->nullable()->after('compte_id');

            // Ajouter la contrainte de clé étrangère
            $table->foreign('compte_destination_id')->references('id')->on('comptes')->onDelete('set null');

            // Ajouter un index pour les performances
            $table->index('compte_destination_id');
        });

        // Pour PostgreSQL, on ne peut pas modifier un enum existant facilement
        // On va plutôt changer la colonne en string et ajouter une contrainte check
        DB::statement("ALTER TABLE transactions ALTER COLUMN type_transaction TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_type_transaction CHECK (type_transaction IN ('depot', 'retrait', 'transfert'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['compte_destination_id']);

            // Supprimer la colonne
            $table->dropColumn('compte_destination_id');
        });

        // Note: On ne peut pas supprimer une valeur d'enum PostgreSQL
        // La valeur 'transfert' restera dans l'enum
    }
};
