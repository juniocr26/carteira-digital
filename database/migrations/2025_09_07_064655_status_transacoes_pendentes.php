<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_transacoes_pendentes', function (Blueprint $table) {
            $table->string('oid_status_transacoes_pendentes', 36)->primary()->default(DB::raw('(UUID())'));
            $table->string('payment_method_id');
            $table->string('situacao_transacao', 2);
            $table->dateTime('data_ultima_atualizacao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_transacoes_pendentes');
    }
};
