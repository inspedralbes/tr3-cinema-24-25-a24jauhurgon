<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migració: cache de vols externs (aviationstack)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volsExternsCache', function (Blueprint $table) {
            $table->id();
            $table->string('externalId')->unique();
            $table->string('origenIata', 3);
            $table->string('destiIata', 3);
            $table->string('flightNumber');
            $table->string('airline');
            $table->dateTime('dataHoraSortidaEstimada');
            $table->dateTime('dataHoraSortidaReal')->nullable();
            $table->string('estat')->default('scheduled');
            $table->json('rawJson')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volsExternsCache');
    }
};
