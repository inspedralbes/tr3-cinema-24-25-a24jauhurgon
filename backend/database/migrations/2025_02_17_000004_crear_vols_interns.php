<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migració: vols interns venibles del sistema
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volsInterns', function (Blueprint $table) {
            $table->id();
            $table->string('externalId')->nullable();
            $table->string('origenIata', 3)->default('BCN');
            $table->string('destiIata', 3);
            $table->dateTime('dataHoraSortida');
            $table->string('estat')->default('programat');
            $table->foreignId('modelAvioId')->constrained('modelsAvio');
            $table->integer('capacitatCompra')->default(10);
            $table->integer('maximBitlletsPerCompra')->default(4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volsInterns');
    }
};
