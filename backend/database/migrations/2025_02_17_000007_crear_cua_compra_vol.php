<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migració: cua d'espera per compra de vol
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuaCompraVol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volId')->constrained('volsInterns')->onDelete('cascade');
            $table->string('clientId');
            $table->string('estat')->default('esperant');
            $table->string('ticket')->unique()->nullable();
            $table->dateTime('ticketExpiraAt')->nullable();
            $table->timestamps();
            $table->index(['volId', 'clientId']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuaCompraVol');
    }
};
