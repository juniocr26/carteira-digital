<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraSaldo extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $table = 'compra_saldo';
    protected $primaryKey = 'oid_compra_saldo';
    protected $keyType = 'string';

    protected $fillable = [
        'cartao_numero',
        'cartao_cvv',
        'cartao_mes',
        'cartao_ano',
        'oid_cartao',
        'cpf',
        'valor_compra',
        'data_transacao',
        'data_pagamento',
        'situacao_transacao'
    ];
}
