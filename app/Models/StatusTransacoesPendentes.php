<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusTransacoesPendentes extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $table = 'status_transacoes_pendentes';
    protected $primaryKey = 'oid_status_transacoes_pendentes';
    protected $keyType = 'string';

    protected $fillable = [
            'payment_method_id',
            'situacao_transacao',
            'data_ultima_atualizacao'
    ];
}
