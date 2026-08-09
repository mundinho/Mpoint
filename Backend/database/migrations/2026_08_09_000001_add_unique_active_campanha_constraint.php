<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Se, por algum bug anterior, existir mais que uma campanha 'ativa', mantém só a
        // mais recente activa e pausa as restantes antes de criar a restrição — caso
        // contrário a migração falharia ao tentar indexar dados já inconsistentes.
        $ativas = DB::table('campanha')->where('estado', 'ativa')->orderByDesc('id')->pluck('id');

        if ($ativas->count() > 1) {
            DB::table('campanha')
                ->where('estado', 'ativa')
                ->where('id', '!=', $ativas->first())
                ->update(['estado' => 'pausada']);
        }

        DB::statement(
            "ALTER TABLE campanha ADD COLUMN estado_ativa_unico TINYINT(1) " .
            "GENERATED ALWAYS AS (IF(estado = 'ativa', 1, NULL)) VIRTUAL"
        );

        DB::statement(
            'ALTER TABLE campanha ADD UNIQUE INDEX campanha_estado_ativa_unico (estado_ativa_unico)'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE campanha DROP INDEX campanha_estado_ativa_unico');
        DB::statement('ALTER TABLE campanha DROP COLUMN estado_ativa_unico');
    }
};
