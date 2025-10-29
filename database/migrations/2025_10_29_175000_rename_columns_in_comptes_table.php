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
            $table->renameColumn('numeroCompte', 'numero_compte');
            $table->renameColumn('dateCreation', 'date_creation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->renameColumn('numero_compte', 'numeroCompte');
            $table->renameColumn('date_creation', 'dateCreation');
        });
    }
};
