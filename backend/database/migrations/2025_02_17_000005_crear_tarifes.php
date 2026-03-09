<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migració: taula de tarifes (general, nen, soci)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->decimal('preu', 8, 2);
            $table->text('descripcio')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifes');
    }
};
