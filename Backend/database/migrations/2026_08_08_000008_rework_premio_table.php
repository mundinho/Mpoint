<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('premio', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn(['categoria_id', 'valor_estimado']);
        });

        DB::statement('ALTER TABLE premio CHANGE descricao nome VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE premio CHANGE nome descricao VARCHAR(255) NOT NULL');

        Schema::table('premio', function (Blueprint $table) {
            $table->decimal('valor_estimado', 10, 2)->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_premio')->nullOnDelete();
        });
    }
};
