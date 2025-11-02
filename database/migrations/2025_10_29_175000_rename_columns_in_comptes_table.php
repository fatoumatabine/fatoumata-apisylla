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
            // Les colonnes sont déjà nommées correctement, cette migration est redondante.
            // $table->renameColumn('numerocompte', 'numero_compte');
            // $table->renameColumn('datecreation', 'date_creation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->renameColumn('numero_compte', 'numerocompte');
            $table->renameColumn('date_creation', 'datecreation');
        });
    }
};
