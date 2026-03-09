<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migració: holds temporals de seients (bloqueig 3 min)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holdsSeients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volId')->constrained('volsInterns')->onDelete('cascade');
            $table->string('clientId');
            $table->integer('fila');
            $table->integer('columna');
            $table->dateTime('expiraAt');
            $table->timestamps();
            $table->unique(['volId', 'fila', 'columna']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holdsSeients');
    }
};
