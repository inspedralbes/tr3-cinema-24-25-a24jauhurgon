<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migració: bitllets individuals dins d'una compra
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitllets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compraId')->constrained('compres')->onDelete('cascade');
            $table->foreignId('volId')->constrained('volsInterns')->onDelete('cascade');
            $table->integer('fila');
            $table->integer('columna');
            $table->string('tipus');
            $table->decimal('preu', 8, 2);
            $table->string('nomPassatger')->nullable();
            $table->timestamps();
            // Restricció: no pot haver-hi dos bitllets al mateix seient
            $table->unique(['volId', 'fila', 'columna']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitllets');
    }
};
