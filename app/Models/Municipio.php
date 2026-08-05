<?php

namespace App\Models;

use App\Models\Cartas\Carta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Municipio extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'municipios';

    protected $fillable = [
        'estado_id',
        'nome',
    ];

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class);
    }

    public function cartas()
    {
        return $this->hasMany(Carta::class);
    }

    public function atividades()
    {
        return $this->belongsToMany(Atividade::class, 'atividade_municipio')
            ->withTimestamps();
    }

    public function getNomeComEstadoAttribute(): string
    {
        $sigla = $this->estado?->sigla; // << usa 'sigla'

        return trim($this->nome.($sigla ? ' - '.$sigla : ''));
    }

    public function isPrioritario(): bool
    {
        if ($this->relationLoaded('estado') && $this->estado && $this->estado->relationLoaded('regiao') && $this->estado->regiao) {
            return $this->estado->regiao->isPrioritaria();
        }

        return (bool) $this->estado?->regiao?->isPrioritaria();
    }

    public function getIsPrioritarioAttribute(): bool
    {
        return $this->isPrioritario();
    }
}
