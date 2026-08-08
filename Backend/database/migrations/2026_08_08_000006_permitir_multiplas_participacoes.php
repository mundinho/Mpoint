<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participacao', function (Blueprint $table) {
            $table->index('campanha_id', 'participacao_campanha_id_index');
            $table->index('usuario_id', 'participacao_usuario_id_index');
        });

        Schema::table('participacao', function (Blueprint $table) {
            $table->dropUnique(['campanha_id', 'usuario_id']);
        });

        DB::statement("ALTER TABLE participacao MODIFY resultado ENUM('pendente','vencedor','nao_vencedor','tentar_novamente') NOT NULL DEFAULT 'pendente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE participacao MODIFY resultado ENUM('pendente','vencedor','nao_vencedor') NOT NULL DEFAULT 'pendente'");

        Schema::table('participacao', function (Blueprint $table) {
            $table->unique(['campanha_id', 'usuario_id']);
        });

        Schema::table('participacao', function (Blueprint $table) {
            $table->dropIndex('participacao_campanha_id_index');
            $table->dropIndex('participacao_usuario_id_index');
        });
    }
};
