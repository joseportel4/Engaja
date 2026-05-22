<?php

namespace Database\Seeders;

use App\Models\Atividade;
use App\Models\Avaliacao;
use App\Models\AvaliacaoQuestao;
use App\Models\Escala;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\Presenca;
use App\Models\Questao;
use App\Models\SubmissaoAvaliacao;
use App\Models\TemplateAvaliacao;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConsolidacaoAvaliacaoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@engaja.local')->first()
            ?? User::factory()->create([
                'name' => 'Admin Engaja',
                'email' => 'admin@engaja.local',
            ]);

        $escala = Escala::first();
        if (! $escala) {
            $escala = Escala::create([
                'descricao' => 'Escala de satisfacao',
                'opcao1' => 'Muito ruim',
                'opcao2' => 'Ruim',
                'opcao3' => 'Regular',
                'opcao4' => 'Bom',
                'opcao5' => 'Excelente',
            ]);
        }

        $templates = $this->ensureTemplates($escala);

        $municipiosPorRegiao = collect([
            'Norte' => $this->municipioPorRegiao('Norte'),
            'Nordeste I' => $this->municipioPorRegiao('Nordeste I'),
            'Nordeste II' => $this->municipioPorRegiao('Nordeste II'),
        ])->filter();

        if ($municipiosPorRegiao->isEmpty()) {
            return;
        }

        $participantes = $this->criarParticipantesPorRegiao($municipiosPorRegiao);

        $eventos = [
            'Como Alfabetizar com Paulo Freire' => [
                'acao_geral' => '2',
                'subacao' => '2.2 - Realizacao de curso EaD "Como Alfabetizar com Paulo Freire"',
                'templates' => $templates->take(2)->values(),
            ],
            'Ação G' => [
                'acao_geral' => '1',
                'subacao' => '1.2 - Formacao aos educandos da EJA',
                'templates' => $templates->slice(1, 2)->values(),
            ],
        ];

        foreach ($eventos as $nome => $config) {
            $evento = Evento::firstOrCreate(
                ['nome' => $nome],
                [
                    'user_id' => $admin->id,
                    'acao_geral' => $config['acao_geral'],
                    'subacao' => $config['subacao'],
                    'data_inicio' => now()->subDays(5)->format('Y-m-d'),
                    'data_fim' => now()->addDays(10)->format('Y-m-d'),
                    'modalidade' => 'Presencial',
                ]
            );

            $atividades = $this->criarAtividadesEvento($evento, $municipiosPorRegiao);
            $templatesEvento = $config['templates'];

            foreach ($atividades as $atividade) {
                $inscricoes = $this->criarInscricoesEPresencas($atividade, $participantes);

                foreach ($templatesEvento as $template) {
                    $avaliacao = Avaliacao::updateOrCreate(
                        [
                            'atividade_id' => $atividade->id,
                            'inscricao_id' => null,
                            'template_avaliacao_id' => $template->id,
                        ],
                        [
                            'anonima' => true,
                        ]
                    );

                    $questoes = $this->sincronizarQuestoes($avaliacao, $template);
                    $this->criarSubmissoesERespostas($avaliacao, $questoes, $inscricoes);
                }
            }
        }
    }

    private function ensureTemplates(Escala $escala): Collection
    {
        $templates = collect([
            'Modelo Consolidacao - Paulo Freire' => [
                'descricao' => 'Modelo com todos os tipos de questao para testes de consolidacao.',
                'questoes' => [
                    ['texto' => 'O que mais marcou no encontro?', 'tipo' => 'texto'],
                    ['texto' => 'A dinamica foi adequada?', 'tipo' => 'escala', 'escala_id' => $escala->id],
                    ['texto' => 'Nota geral de 0 a 10', 'tipo' => 'numero'],
                    ['texto' => 'Recomendaria este encontro?', 'tipo' => 'boolean'],
                    [
                        'texto' => 'Nivel de satisfacao',
                        'tipo' => 'unica',
                        'opcoes' => ['Otimo', 'Bom', 'Regular', 'Ruim'],
                    ],
                    [
                        'texto' => 'O que mais ajudou no aprendizado?',
                        'tipo' => 'multipla',
                        'opcoes' => ['Conteudo', 'Metodologia', 'Material', 'Trocas'],
                    ],
                ],
            ],
            'Modelo Consolidacao - Engajamento' => [
                'descricao' => 'Modelo extra para validar mais de um template por acao.',
                'questoes' => [
                    ['texto' => 'Como voce avalia o engajamento?', 'tipo' => 'escala', 'escala_id' => $escala->id],
                    ['texto' => 'O tempo foi suficiente?', 'tipo' => 'boolean'],
                    ['texto' => 'Que temas voce quer aprofundar?', 'tipo' => 'texto'],
                    [
                        'texto' => 'Qual formato preferiu?',
                        'tipo' => 'unica',
                        'opcoes' => ['Palestra', 'Oficina', 'Roda de conversa'],
                    ],
                    [
                        'texto' => 'O que foi mais util?',
                        'tipo' => 'multipla',
                        'opcoes' => ['Conteudo', 'Pratica', 'Materiais', 'Trocas'],
                    ],
                ],
            ],
            'Modelo Consolidacao - Acao G' => [
                'descricao' => 'Modelo alternativo para validar agrupamentos e medias.',
                'questoes' => [
                    ['texto' => 'Comentario geral sobre a acao', 'tipo' => 'texto'],
                    ['texto' => 'A organizacao foi eficiente?', 'tipo' => 'escala', 'escala_id' => $escala->id],
                    ['texto' => 'Em uma escala de 0 a 10, como avaliaria?', 'tipo' => 'numero'],
                    ['texto' => 'Participaria novamente?', 'tipo' => 'boolean'],
                    [
                        'texto' => 'Qual foi o destaque do encontro?',
                        'tipo' => 'unica',
                        'opcoes' => ['Equipe', 'Conteudo', 'Local', 'Materiais'],
                    ],
                    [
                        'texto' => 'O que poderia melhorar?',
                        'tipo' => 'multipla',
                        'opcoes' => ['Tempo', 'Infraestrutura', 'Metodologia', 'Divulgacao'],
                    ],
                ],
            ],
        ]);

        return $templates->map(function (array $dados, string $nome) {
            $template = TemplateAvaliacao::firstOrCreate(
                ['nome' => $nome],
                ['descricao' => $dados['descricao']]
            );

            $ordem = 1;
            foreach ($dados['questoes'] as $questao) {
                Questao::updateOrCreate(
                    [
                        'template_avaliacao_id' => $template->id,
                        'texto' => $questao['texto'],
                    ],
                    [
                        'tipo' => $questao['tipo'],
                        'escala_id' => $questao['escala_id'] ?? null,
                        'opcoes_resposta' => $questao['opcoes'] ?? null,
                        'ordem' => $ordem++,
                        'fixa' => false,
                    ]
                );
            }

            return $template;
        })->values();
    }

    private function municipioPorRegiao(string $nome): ?Municipio
    {
        return Municipio::query()
            ->whereHas('estado.regiao', fn ($q) => $q->where('nome', $nome))
            ->inRandomOrder()
            ->first();
    }

    private function criarParticipantesPorRegiao(Collection $municipiosPorRegiao): Collection
    {
        $participantes = collect();
        $sequencia = 1;

        foreach ($municipiosPorRegiao as $regiao => $municipio) {
            for ($i = 1; $i <= 3; $i++) {
                $email = Str::of($regiao)
                    ->lower()
                    ->replace(' ', '')
                    ->append('.teste', $i, '@engaja.local')
                    ->toString();

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => ucfirst($regiao).' Tester '.$i,
                        'password' => bcrypt('password'),
                        'email_verified_at' => now(),
                    ]
                );

                $participante = Participante::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'municipio_id' => $municipio->id,
                        'cpf' => str_pad((string) $sequencia, 11, '0', STR_PAD_LEFT),
                        'telefone' => '(11) 90000-'.str_pad((string) $sequencia, 4, '0', STR_PAD_LEFT),
                        'escola_unidade' => 'Escola Teste '.$regiao,
                        'tipo_organizacao' => 'Rede municipal',
                        'tag' => Participante::TAG_REDE_ENSINO,
                        'data_entrada' => now()->subDays(30)->format('Y-m-d'),
                    ]
                );

                $participantes->push($participante);
                $sequencia++;
            }
        }

        return $participantes;
    }

    private function criarAtividadesEvento(Evento $evento, Collection $municipiosPorRegiao): Collection
    {
        $rotas = $municipiosPorRegiao->values();
        $descricoes = [
            'Momento Norte',
            'Momento Nordeste I',
            'Momento Nordeste II',
        ];

        return collect($descricoes)->map(function (string $descricao, int $index) use ($evento, $rotas) {
            $municipio = $rotas[$index % $rotas->count()];

            return Atividade::updateOrCreate(
                [
                    'evento_id' => $evento->id,
                    'descricao' => $descricao,
                ],
                [
                    'municipio_id' => $municipio->id,
                    'dia' => now()->addDays($index)->format('Y-m-d'),
                    'hora_inicio' => '09:00',
                    'hora_fim' => '12:00',
                    'publico_esperado' => 40 + ($index * 10),
                    'carga_horaria' => 180,
                    'presenca_ativa' => true,
                ]
            );
        });
    }

    private function sincronizarQuestoes(Avaliacao $avaliacao, TemplateAvaliacao $template): Collection
    {
        $questoesTemplate = $template->questoes()->orderBy('ordem')->orderBy('id')->get();
        $idsMantidos = [];

        foreach ($questoesTemplate as $questao) {
            $payload = [
                'questao_id' => $questao->id,
                'indicador_id' => $questao->indicador_id,
                'escala_id' => $questao->tipo === 'escala' ? $questao->escala_id : null,
                'evidencia_id' => $questao->evidencia_id,
                'texto' => $questao->texto,
                'tipo' => $questao->tipo,
                'opcoes_resposta' => $questao->opcoes_resposta,
                'ordem' => $questao->ordem,
                'fixa' => (bool) $questao->fixa,
            ];

            $avaliacaoQuestao = AvaliacaoQuestao::updateOrCreate(
                [
                    'avaliacao_id' => $avaliacao->id,
                    'questao_id' => $questao->id,
                ],
                $payload
            );

            $idsMantidos[] = $avaliacaoQuestao->id;
        }

        if (! empty($idsMantidos)) {
            $avaliacao->avaliacaoQuestoes()
                ->whereNotIn('id', $idsMantidos)
                ->delete();
        }

        return $avaliacao->avaliacaoQuestoes()->with('escala')->whereIn('id', $idsMantidos)->get();
    }

    private function criarInscricoesEPresencas(Atividade $atividade, Collection $participantes): Collection
    {
        $inscricoes = collect();
        $amostra = $participantes->shuffle()->take(6);

        foreach ($amostra as $participante) {
            $inscricao = Inscricao::firstOrCreate(
                [
                    'atividade_id' => $atividade->id,
                    'participante_id' => $participante->id,
                ],
                [
                    'evento_id' => $atividade->evento_id,
                ]
            );

            $inscricoes->push($inscricao);

            Presenca::firstOrCreate(
                [
                    'inscricao_id' => $inscricao->id,
                    'atividade_id' => $atividade->id,
                ],
                [
                    'status' => 'presente',
                ]
            );
        }

        return $inscricoes;
    }

    private function criarSubmissoesERespostas(
        Avaliacao $avaliacao,
        Collection $questoes,
        Collection $inscricoes
    ): void {
        if ($questoes->isEmpty() || $inscricoes->isEmpty()) {
            return;
        }

        foreach ($inscricoes as $index => $inscricao) {
            $codigo = 'CONS-'.$avaliacao->id.'-'.$inscricao->id;

            $submissao = SubmissaoAvaliacao::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'atividade_id' => $avaliacao->atividade_id,
                    'avaliacao_id' => $avaliacao->id,
                ]
            );

            foreach ($questoes as $questao) {
                $resposta = $this->gerarRespostaParaQuestao($questao, $index);

                $avaliacao->respostas()->updateOrCreate(
                    [
                        'avaliacao_id' => $avaliacao->id,
                        'avaliacao_questao_id' => $questao->id,
                        'submissao_avaliacao_id' => $submissao->id,
                    ],
                    [
                        'resposta' => $resposta,
                    ]
                );
            }

            $presenca = Presenca::where('atividade_id', $avaliacao->atividade_id)
                ->where('inscricao_id', $inscricao->id)
                ->first();

            if ($presenca) {
                $presenca->avaliacao_respondida = true;
                $presenca->save();
            }
        }
    }

    private function gerarRespostaParaQuestao(AvaliacaoQuestao $questao, int $offset): string
    {
        $tipo = $questao->tipo;

        if ($tipo === 'escala') {
            $opcoes = $questao->escala?->valores ?? [];
            if (! empty($opcoes)) {
                return $opcoes[$offset % count($opcoes)];
            }

            return 'Regular';
        }

        if ($tipo === 'numero') {
            return (string) (6 + ($offset % 5));
        }

        if ($tipo === 'boolean') {
            return $offset % 2 === 0 ? '1' : '0';
        }

        if ($tipo === 'unica') {
            $opcoes = $questao->opcoes_resposta ?? [];
            if (is_array($opcoes) && ! empty($opcoes)) {
                return (string) $opcoes[$offset % count($opcoes)];
            }
        }

        if ($tipo === 'multipla') {
            $opcoes = $questao->opcoes_resposta ?? [];
            if (is_array($opcoes) && ! empty($opcoes)) {
                $primeiro = $opcoes[$offset % count($opcoes)];
                $segundo = $opcoes[($offset + 1) % count($opcoes)];

                return json_encode([$primeiro, $segundo]);
            }
        }

        $comentarios = [
            'Achei o encontro bem organizado.',
            'O conteudo dialogou com a minha realidade.',
            'Gostaria de mais momentos de troca.',
            'A equipe facilitou muito o processo.',
            'Foi util para planejar proximas acoes.',
            'As dinamicas foram participativas.',
            'Senti falta de mais exemplos concretos.',
            'Os materiais de apoio foram claros.',
            'O tempo para perguntas foi suficiente.',
            'Voltaria a participar de novas edicoes.',
        ];

        return $comentarios[$offset % count($comentarios)];
    }
}
