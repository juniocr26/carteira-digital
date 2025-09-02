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
        'payment_method_id',
        'valor_compra',
        'situacao_transacao',
        'nome',
        'cpf',
        'data_transacao',
        'data_pagamento',
    ];
}
