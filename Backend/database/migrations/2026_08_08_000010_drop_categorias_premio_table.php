<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('categorias_premio');
    }

    public function down(): void
    {
        Schema::create('categorias_premio', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo')->default('normal');
            $table->timestamps();
        });
    }
};
