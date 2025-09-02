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
            $table->string('payment_method_id');
            $table->string('zip_code');
            $table->decimal('valor_compra', 10, 2)->default(0);
            $table->string('situacao_transacao', 2);
            $table->string('nome');
            $table->string('cpf', 20);
            $table->dateTime('data_transacao')->nullable();
            $table->dateTime('data_pagamento')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_saldo');
    }
};
