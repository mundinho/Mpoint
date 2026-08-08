<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categoria_premio', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('tipo', ['normal', 'tentar_novamente'])->default('normal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_premio');
    }
};
