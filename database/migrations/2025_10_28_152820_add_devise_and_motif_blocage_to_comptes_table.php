<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->string('devise')->default('FCFA')->after('telephone');
            $table->decimal('solde_initial', 15, 2)->default(0)->after('devise');
            $table->string('motif_blocage')->nullable()->after('status_compte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn(['devise', 'solde_initial', 'motif_blocage']);
        });
    }
};