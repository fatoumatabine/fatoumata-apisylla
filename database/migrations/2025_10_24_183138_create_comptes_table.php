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
            $table->string('numeroCompte')->unique();
            $table->string('titulaire');
            $table->enum('type', ['epargne', 'cheque']);
            $table->decimal('solde', 15, 2)->default(0);
            $table->string('devise');
            $table->timestamp('dateCreation')->useCurrent();
            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
            $table->json('metadata')->nullable();
            $table->uuid('client_id')->nullable(); // Assuming a Client model with UUID primary key
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');

            $table->index('numeroCompte');
            $table->index('client_id');
            $table->softDeletes(); // Add soft deletes
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
