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
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_compte');
            $table->enum('type_compte', ['epargne', 'cheque']);
            $table->enum('status_compte', ['active', 'bloque']);
            $table->string('telephone');
            $table->foreignUuid('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->timestamps();

            $table->index('client_id');
            $table->index('status_compte');
            $table->index('type_compte');
            $table->index(['client_id', 'status_compte']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
