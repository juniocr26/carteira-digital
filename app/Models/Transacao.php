<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transacao extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $table = 'transacao';
    protected $primaryKey = 'oid_transacao';
    protected $keyType = 'string';

    protected $fillable = [
            'payment_method_id',
            'valor_compra',
            'situacao_transacao',
            'descricao_transacao',
            'tipo_transacao',
            'nome',
            'cpf',
            'data_transacao',
            'data_pagamento'
    ];
}
