<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('premio', function (Blueprint $table) {
            $table->renameColumn('descricao', 'nome');
            $table->unsignedInteger('quantidade')->default(1)->after('nome');
            $table->text('logica_aleatoriedade')->nullable()->after('data_programada');
            $table->enum('especial', ['normal', 'tentar_novamente'])->default('normal')->after('logica_aleatoriedade');
        });
    }

    public function down(): void
    {
        Schema::table('premio', function (Blueprint $table) {
            $table->dropColumn(['quantidade', 'logica_aleatoriedade', 'especial']);
            $table->renameColumn('nome', 'descricao');
        });
    }
};
