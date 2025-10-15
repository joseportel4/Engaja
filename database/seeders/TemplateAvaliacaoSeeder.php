<?php

namespace Database\Seeders;

use App\Models\Questao;
use App\Models\TemplateAvaliacao;
use Illuminate\Database\Seeder;

class TemplateAvaliacaoSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'Avaliação padrão de atividades' => [
                'descricao' => 'Coleta percepções gerais sobre a realização das atividades.',
                'questoes'  => [
                    'Os objetivos da atividade foram apresentados com clareza?',
                    'O conteúdo abordado dialogou com a sua realidade?',
                    'As propostas possibilitaram interação entre participantes?',
                    'Em uma escala de 0 a 10, qual a chance de participar de novas ações?',
                    'Quais melhorias você sugere para a infraestrutura do evento?',
                ],
            ],
            'Avaliação de logística e comunicação' => [
                'descricao' => 'Foco em aspectos de apoio, comunicação e estrutura das ações.',
                'questoes'  => [
                    'A comunicação antes do evento foi suficiente?',
                    'Quais melhorias você sugere para a infraestrutura do evento?',
                    'Houve atividades práticas relacionadas aos conceitos apresentados?',
                ],
            ],
        ];

        $questoesMap = Questao::pluck('id', 'texto');

        foreach ($templates as $nome => $dados) {
            $template = TemplateAvaliacao::updateOrCreate(
                ['nome' => $nome],
                ['descricao' => $dados['descricao']]
            );

            $pivotData = [];
            $ordem = 1;

            foreach ($dados['questoes'] as $textoQuestao) {
                $questaoId = $questoesMap[$textoQuestao] ?? null;

                if (! $questaoId) {
                    continue;
                }

                $pivotData[$questaoId] = ['ordem' => $ordem++];
            }

            if (! empty($pivotData)) {
                $template->questoes()->sync($pivotData);
            }
        }
    }
}
