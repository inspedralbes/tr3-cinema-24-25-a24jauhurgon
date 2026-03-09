<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migració: taula modelsAvio per definir configuracions d'avions
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelsAvio', function (Blueprint $table) {
            $table->id();
            $table->string('nomModel');
            $table->integer('files');
            $table->integer('columnes');
            $table->integer('seientsTotals');
            $table->text('descripcio')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelsAvio');
    }
};
