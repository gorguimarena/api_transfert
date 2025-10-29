<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('numero_compte');
            $table->enum('type_compte', ['epargne', 'cheque']);
            $table->enum('status_compte', ['active', 'bloque']);
            $table->string('telephone');
            $table->uuid('client_id');
            $table->boolean('is_deleted');
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('block_end_date')->nullable();
            $table->string('block_reason')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->string('devise')->default('FCFA')->after('telephone');
            $table->string('motif_blocage')->nullable()->after('status_compte');
            $table->timestamps();
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
