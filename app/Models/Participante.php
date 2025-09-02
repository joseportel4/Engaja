<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participante extends Model
{
    use SoftDeletes;
    protected $table = 'participantes';
    protected $fillable = ['user_id', 'municipio_id', 'cpf', 'telefone', 'escola_unidade', 'data_entrada'];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
    public function municipio(){
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
    public function inscricoes(){
        return $this->hasMany(Inscricao::class, 'participante_id');
    }
    public function eventos(){
        return $this->belongsToMany(Evento::class, 'inscricaos')->withTimestamps();
    }

}
