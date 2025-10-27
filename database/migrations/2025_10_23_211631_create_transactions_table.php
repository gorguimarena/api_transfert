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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('montant', 15, 2);
            $table->enum('type_transaction', ['depot', 'retrait']);
            $table->foreignUuid('compte_id')->references('id')->on('comptes')->onDelete('cascade');
            $table->timestamps();

            $table->index('compte_id');
            $table->index('type_transaction');
            $table->index('created_at');
            $table->index(['compte_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
