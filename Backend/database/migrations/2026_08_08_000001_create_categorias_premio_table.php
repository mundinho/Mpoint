<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_premio', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo')->default('normal');
            $table->timestamps();
        });

        DB::table('categorias_premio')->insert([
            'nome' => 'Tente novamente',
            'tipo' => 'tentar_novamente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_premio');
    }
};
