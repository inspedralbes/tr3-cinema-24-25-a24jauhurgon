<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migració: control de capacitat de compra per vol
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlCompraVol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volId')->constrained('volsInterns')->onDelete('cascade');
            $table->integer('actius')->default(0);
            $table->integer('capacitat')->default(10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlCompraVol');
    }
};
