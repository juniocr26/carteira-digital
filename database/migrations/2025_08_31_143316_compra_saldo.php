<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_saldo', function (Blueprint $table) {
            $table->string('oid_compra_saldo', 36)->primary()->default(DB::raw('(UUID())'));
            $table->string('cartao_numero', 19);
            $table->string('cartao_cvv', 3);
            $table->string('cartao_mes', 2);
            $table->string('cartao_ano', 4);
            $table->string('oid_cartao', 36);
            $table->string('cpf', 11);
            $table->decimal('valor_compra', 10, 2)->default(0);
            $table->string('situacao_transacao', 2);
            $table->dateTime('data_transacao')->nullable();
            $table->dateTime('data_pagamento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_saldo');
    }
};
