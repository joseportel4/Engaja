<?php

namespace App\Exports;

use App\Services\ParticipantesExclusivosService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ParticipantesExclusivosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @param  array<int>  $eventoIds
     */
    public function __construct(private array $eventoIds)
    {
    }

    public function collection(): Collection
    {
        return app(ParticipantesExclusivosService::class)
            ->query($this->eventoIds)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nome',
            'Email',
            'CPF',
            'Telefone',
            'Município',
            'Tipo de instituição',
            'Instituição',
            'Vínculo no projeto',
        ];
    }

    public function map($row): array
    {
        return [
            $row->nome,
            $row->email,
            $row->cpf,
            $row->telefone,
            $this->formatMunicipioEstado($row->municipio, $row->estado),
            $row->tipo_organizacao,
            $row->escola_unidade,
            $row->tag,
        ];
    }

    private function formatMunicipioEstado(?string $municipio, ?string $estado): ?string
    {
        if (!$municipio && !$estado) {
            return null;
        }

        if ($municipio && $estado) {
            return $municipio . ' - ' . $estado;
        }

        return $municipio ?: $estado;
    }
}
