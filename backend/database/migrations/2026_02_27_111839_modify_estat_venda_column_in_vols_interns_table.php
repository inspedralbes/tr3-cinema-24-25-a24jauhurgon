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
            $table->string('estat_venda', 20)->default('tancat')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('volsInterns', function (Blueprint $table) {
            $table->enum('estat_venda', ['tancada', 'oberta'])->default('tancada')->change();
        });
    }
};
