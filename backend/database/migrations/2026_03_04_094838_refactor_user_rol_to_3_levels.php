<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Migració de neteja: elimina columna esSoci (ara integrada al camp 'rol')
// Segura en Fresh migrations: comprova existència de la columna abans d'actuar
return new class extends Migration
{
    public function up(): void
    {
        // Només actua si l'antiga columna esSoci encara existeix (instàncies migrades)
        if (Schema::hasColumn('users', 'esSoci')) {
            // Migrar els usuaris amb esSoci=true a rol='premium'
            DB::table('users')
                ->where('esSoci', true)
                ->where('rol', '!=', 'admin')
                ->update(['rol' => 'premium']);

            // Eliminar la columna esSoci
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('esSoci');
            });
        }

        // Normalitzar qualsevol 'usuari' residual -> 'general'
        DB::table('users')
            ->where('rol', 'usuari')
            ->update(['rol' => 'general']);
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'esSoci')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('esSoci')->default(false)->after('rol');
            });
        }
    }
};
