<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Saldo extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $table = 'saldo';
    protected $primaryKey = 'oid_saldo';
    protected $keyType = 'string';

    protected $fillable = [
            'cpf',
            'saldo',
            'data_ultima_atualizacao'
    ];
}
