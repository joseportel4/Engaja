<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IbgeLocalidadesService
{
    private const BASE_URL = 'https://servicodados.ibge.gov.br/api/v1/localidades';

    /** @return array<int, array{id: int, nome: string, sigla: string}> */
    public function estados(): array
    {
        return Cache::remember('cartas.ibge.estados', now()->addDays(30), function (): array {
            return $this->request('/estados')
                ->sortBy('nome', SORT_NATURAL | SORT_FLAG_CASE)
                ->map(fn (array $estado) => [
                    'id' => (int) $estado['id'],
                    'nome' => $estado['nome'],
                    'sigla' => $estado['sigla'],
                ])->values()->all();
        });
    }

    /** @return array<int, array{id: int, nome: string}> */
    public function municipiosDoEstado(int $estadoIbgeId): array
    {
        return Cache::remember("cartas.ibge.estados.{$estadoIbgeId}.municipios", now()->addDays(30), function () use ($estadoIbgeId): array {
            return $this->request("/estados/{$estadoIbgeId}/municipios")
                ->sortBy('nome', SORT_NATURAL | SORT_FLAG_CASE)
                ->map(fn (array $municipio) => [
                    'id' => (int) $municipio['id'],
                    'nome' => $municipio['nome'],
                ])->values()->all();
        });
    }

    /** @return array{estado: array{id: int, nome: string, sigla: string}, municipio: array{id: int, nome: string}} */
    public function localizar(int $estadoIbgeId, int $municipioIbgeId): array
    {
        $estado = collect($this->estados())->firstWhere('id', $estadoIbgeId);
        $municipio = collect($this->municipiosDoEstado($estadoIbgeId))->firstWhere('id', $municipioIbgeId);

        if (! $estado || ! $municipio) {
            throw new RuntimeException('A localidade selecionada não foi encontrada no IBGE.');
        }

        return compact('estado', 'municipio');
    }

    private function request(string $path)
    {
        try {
            return Http::withoutVerifying()->acceptJson()->timeout(5)->retry(2, 100)
                ->get(self::BASE_URL.$path)->throw()->collect();
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException('Não foi possível consultar as localidades do IBGE.', previous: $exception);
        }
    }
}
