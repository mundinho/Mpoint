<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Links com vários níveis (ex.: "mbottlestore.rifa"): o slug passa a ter até 63 caracteres.
 * SQL directo para não depender do doctrine/dbal; o índice único mantém-se.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE campanha MODIFY slug VARCHAR(63) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE campanha MODIFY slug VARCHAR(40) NULL');
    }
};
