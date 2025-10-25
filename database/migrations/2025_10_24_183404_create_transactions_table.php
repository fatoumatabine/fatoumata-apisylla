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
            $table->uuid('compte_id');
            $table->enum('type', ['depot', 'retrait', 'virement']);
            $table->decimal('montant', 15, 2);
            $table->string('devise');
            $table->string('description')->nullable();
            $table->timestamp('dateTransaction')->useCurrent();
            $table->timestamps();

            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');

            $table->index('compte_id');
            $table->index('type');
            $table->index('dateTransaction');
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
