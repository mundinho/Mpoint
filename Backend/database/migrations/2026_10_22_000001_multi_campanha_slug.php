<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Várias campanhas activas em simultâneo, cada uma com o seu link (slug):
 * - remove a restrição de uma única campanha 'ativa';
 * - acrescenta o slug único; as campanhas existentes recebem "campanha-{id}".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('campanha', 'estado_ativa_unico')) {
            DB::statement('ALTER TABLE campanha DROP INDEX campanha_estado_ativa_unico');
            DB::statement('ALTER TABLE campanha DROP COLUMN estado_ativa_unico');
        }

        Schema::table('campanha', function (Blueprint $table) {
            $table->string('slug', 40)->nullable()->unique()->after('nome');
        });

        DB::table('campanha')->whereNull('slug')->update(['slug' => DB::raw("CONCAT('campanha-', id)")]);
    }

    public function down(): void
    {
        Schema::table('campanha', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });

        // Repor a restrição de uma só campanha activa (mantém a mais recente)
        $ativas = DB::table('campanha')->where('estado', 'ativa')->orderByDesc('id')->pluck('id');
        if ($ativas->count() > 1) {
            DB::table('campanha')->where('estado', 'ativa')->where('id', '!=', $ativas->first())->update(['estado' => 'pausada']);
        }
        DB::statement("ALTER TABLE campanha ADD COLUMN estado_ativa_unico TINYINT(1) GENERATED ALWAYS AS (IF(estado = 'ativa', 1, NULL)) VIRTUAL");
        DB::statement('ALTER TABLE campanha ADD UNIQUE INDEX campanha_estado_ativa_unico (estado_ativa_unico)');
    }
};
