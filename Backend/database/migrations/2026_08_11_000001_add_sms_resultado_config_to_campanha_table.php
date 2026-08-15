<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campanha', function (Blueprint $table) {
            $table->boolean('sms_resultado_ativo')->default(true)->after('otp_validade_minutos');
            $table->text('texto_sms_resultado')->nullable()->after('sms_resultado_ativo');
        });
    }

    public function down(): void
    {
        Schema::table('campanha', function (Blueprint $table) {
            $table->dropColumn(['sms_resultado_ativo', 'texto_sms_resultado']);
        });
    }
};
