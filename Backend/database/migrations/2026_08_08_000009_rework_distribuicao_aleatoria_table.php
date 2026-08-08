<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distribuicao_aleatoria', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
            $table->string('nome')->default('')->after('campanha_id');
            $table->string('logica_aleatoriedade')->nullable()->after('quantidade');
        });
    }

    public function down(): void
    {
        Schema::table('distribuicao_aleatoria', function (Blueprint $table) {
            $table->dropColumn(['nome', 'logica_aleatoriedade']);
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_premio')->nullOnDelete();
        });
    }
};
