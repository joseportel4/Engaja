<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Avaliacao;
use App\Models\Inscricao;
use App\Models\TemplateAvaliacao;
use Illuminate\Http\Request;

class AvaliacaoController extends Controller
{
    public function index()
    {
        $avaliacoes = Avaliacao::with([
            'inscricao.participante.user',
            'inscricao.evento',
            'atividade.evento',
            'templateAvaliacao',
        ])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('avaliacoes.index', compact('avaliacoes'));
    }

    public function create(Request $request)
    {
        $inscricoes = Inscricao::with(['participante.user', 'evento'])
            ->orderByDesc('created_at')
            ->get();

        $atividades = Atividade::with('evento')
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        $templates = TemplateAvaliacao::with(['questoes.escala', 'questoes.indicador.dimensao'])
            ->orderBy('nome')
            ->get();

        return view('avaliacoes.create', compact(
            'inscricoes',
            'atividades',
            'templates'
        ));
    }

    public function store(Request $request)
    {
        $dados = $this->validateAvaliacao($request);

        $duplicada = Avaliacao::where('inscricao_id', $dados['inscricao_id'])
            ->where('atividade_id', $dados['atividade_id'])
            ->exists();

        if ($duplicada) {
            return back()
                ->withInput()
                ->withErrors(['atividade_id' => 'Já existe uma avaliação para esta inscrição nesta atividade.']);
        }

        DB::transaction(function () use ($dados, $request) {
            $avaliacao = Avaliacao::create($dados);

            $this->sincronizaRespostas($avaliacao, $request->input('respostas', []));
        });

        return redirect()
            ->route('avaliacoes.index')
            ->with('success', 'Avaliação registrada com sucesso!');
    }

    public function show(Avaliacao $avaliacao)
    {
        $avaliacao->load([
            'inscricao.participante.user',
            'inscricao.evento',
            'atividade.evento',
            'templateAvaliacao.questoes.escala',
            'respostas.questao',
        ]);

        return view('avaliacoes.show', [
            'avaliacao' => $avaliacao,
        ]);
    }

    public function edit(Avaliacao $avaliacao)
    {
        $avaliacao->load([
            'templateAvaliacao.questoes.escala',
            'respostas',
            'inscricao.participante.user',
            'inscricao.evento',
            'atividade.evento',
        ]);

        $inscricoes = Inscricao::with(['participante.user', 'evento'])
            ->orderByDesc('created_at')
            ->get();

        $atividades = Atividade::with('evento')
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        $templates = TemplateAvaliacao::with(['questoes.escala', 'questoes.indicador.dimensao'])
            ->orderBy('nome')
            ->get();

        return view('avaliacoes.edit', [
            'avaliacao'          => $avaliacao,
            'inscricoes'         => $inscricoes,
            'atividades'         => $atividades,
            'templates'          => $templates,
            'templateSelecionado'=> $avaliacao->templateAvaliacao,
        ]);
    }

    public function update(Request $request, Avaliacao $avaliacao)
    {
        $dados = $this->validateAvaliacao($request, $avaliacao->id);

        $duplicada = Avaliacao::where('inscricao_id', $dados['inscricao_id'])
            ->where('atividade_id', $dados['atividade_id'])
            ->where('id', '<>', $avaliacao->id)
            ->exists();

        if ($duplicada) {
            return back()
                ->withInput()
                ->withErrors(['atividade_id' => 'Já existe outra avaliação para esta inscrição nesta atividade.']);
        }

        DB::transaction(function () use ($avaliacao, $dados, $request) {
            $avaliacao->update($dados);
            $this->sincronizaRespostas($avaliacao, $request->input('respostas', []));
        });

        return redirect()
            ->route('avaliacoes.index')
            ->with('success', 'Avaliação atualizada com sucesso!');
    }

    public function destroy(Avaliacao $avaliacao)
    {
        $avaliacao->delete();

        return redirect()
            ->route('avaliacoes.index')
            ->with('success', 'Avaliação removida com sucesso!');
    }

    private function sincronizaRespostas(Avaliacao $avaliacao, array $respostas): void
    {
        $template = $avaliacao->templateAvaliacao()->with('questoes')->first();
        $avaliacao->respostas()->delete();

        if (! $template) {
            return;
        }

        foreach ($template->questoes as $questao) {
            $valor = $respostas[$questao->id] ?? null;

            if ($valor === null || $valor === '') {
                continue;
            }

            $avaliacao->respostas()->create([
                'questao_id' => $questao->id,
                'resposta'   => is_array($valor) ? json_encode($valor) : $valor,
            ]);
        }
    }

    private function validateAvaliacao(Request $request, ?int $avaliacaoId = null): array
    {
        $dados = $request->validate([
            'inscricao_id'          => ['required', Rule::exists('inscricaos', 'id')],
            'atividade_id'          => ['required', Rule::exists('atividades', 'id')],
            'template_avaliacao_id' => ['required', Rule::exists('template_avaliacaos', 'id')],
            'respostas'             => ['nullable', 'array'],
        ]);

        return [
            'inscricao_id'          => $dados['inscricao_id'],
            'atividade_id'          => $dados['atividade_id'],
            'template_avaliacao_id' => $dados['template_avaliacao_id'],
        ];
    }
}
