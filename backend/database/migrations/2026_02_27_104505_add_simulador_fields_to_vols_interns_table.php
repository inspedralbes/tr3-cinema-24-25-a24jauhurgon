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
        Schema::table('volsInterns', function (Blueprint $table) {
            $table->string('vol_entrant_origen', 10)->nullable()->comment('Ex: MAD, PMI, SVQ. Origen del vol que arriba a BCN');
            $table->timestamp('hora_arribada_esperada')->nullable()->comment('ETA del vol entrant a BCN');
            $table->enum('estat_venda', ['tancada', 'oberta'])->default('tancada')->comment('Estat de venda de bitllets per al següent vol relacionat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('volsInterns', function (Blueprint $table) {
            $table->dropColumn(['vol_entrant_origen', 'hora_arribada_esperada', 'estat_venda']);
        });
    }
};
