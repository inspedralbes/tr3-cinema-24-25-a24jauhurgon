<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migració: taula de compres confirmades
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volId')->constrained('volsInterns')->onDelete('cascade');
            $table->foreignId('usuariId')->nullable()->constrained('users')->onDelete('set null');
            $table->string('email');
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compres');
    }
};
