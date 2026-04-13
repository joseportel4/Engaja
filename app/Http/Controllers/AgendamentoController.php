<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\AtividadeAcao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AgendamentoController extends Controller
{
    public function index()
    {
        $agendamentos = Agendamento::query()
            ->with(['atividadeAcao', 'municipio.estado', 'user', 'atividade.evento'])
            ->withCount('participantesClonados')
            ->where('user_id', auth()->id())
            ->orderBy('data_horario')
            ->paginate(15);

        return view('agendamentos.index', compact('agendamentos'));
    }

    public function create()
    {
        $atividadeAcoes = AtividadeAcao::query()
            ->orderBy('nome')
            ->get();

        $municipio = $this->municipioDoUsuario(auth()->user());

        return view('agendamentos.create', compact('atividadeAcoes', 'municipio'));
    }

    public function store(Request $request)
    {
        $dados = $this->validarDados($request);

        $municipio = $this->municipioDoUsuario(auth()->user());
        if (!$municipio) {
            return back()
                ->withInput()
                ->withErrors(['municipio_id' => 'Seu participante não possui município preenchido. Atualize seu perfil para cadastrar agendamentos.']);
        }

        $dados['municipio_id'] = $municipio->id;
        $dados['user_id'] = auth()->id();

        Agendamento::create($dados);

        return redirect()
            ->route('agendamentos.index')
            ->with('success', 'Agendamento criado com sucesso.');
    }

    public function show(Agendamento $agendamento)
    {
        $agendamento->load(['atividadeAcao', 'municipio.estado', 'user', 'atividade.evento'])
            ->loadCount('participantesClonados');

        return view('agendamentos.show', compact('agendamento'));
    }

    public function edit(Agendamento $agendamento)
    {
        $this->garantirNaoEfetivado($agendamento);

        $atividadeAcoes = AtividadeAcao::query()
            ->orderBy('nome')
            ->get();

        $municipio = $this->municipioDoUsuario(auth()->user());

        return view('agendamentos.edit', compact('agendamento', 'atividadeAcoes', 'municipio'));
    }

    public function update(Request $request, Agendamento $agendamento)
    {
        $this->garantirNaoEfetivado($agendamento);

        $dados = $this->validarDados($request);

        $municipio = $this->municipioDoUsuario(auth()->user());
        if (!$municipio) {
            return back()
                ->withInput()
                ->withErrors(['municipio_id' => 'Seu participante não possui município preenchido. Atualize seu perfil para cadastrar agendamentos.']);
        }

        $dados['municipio_id'] = $municipio->id;
        $dados['user_id'] = auth()->id();

        $agendamento->update($dados);

        return redirect()
            ->route('agendamentos.index')
            ->with('success', 'Agendamento atualizado com sucesso.');
    }

    public function destroy(Agendamento $agendamento)
    {
        $this->garantirNaoEfetivado($agendamento);

        $agendamento->delete();

        return redirect()
            ->route('agendamentos.index')
            ->with('success', 'Agendamento removido com sucesso.');
    }

    private function validarDados(Request $request): array
    {
        $dados = $request->validate([
            'data_horario' => 'required|date',
            'atividade_acao_id' => 'required|exists:atividade_acoes,id',
            'publico_participante' => 'required|string|max:255',
            'local_acao' => 'required|string|max:255',
            'turma' => 'nullable|string|max:255',
        ]);

        $atividadeAcao = AtividadeAcao::query()->findOrFail($dados['atividade_acao_id']);

        if (!$atividadeAcao->usa_turmas) {
            $dados['turma'] = null;
            return $dados;
        }

        $turmasDisponiveis = $atividadeAcao->turmas_configuradas;
        $turmaSelecionada = trim((string) ($dados['turma'] ?? ''));

        if ($turmaSelecionada === '') {
            throw ValidationException::withMessages([
                'turma' => 'Selecione a turma para este agendamento.',
            ]);
        }

        if (!in_array($turmaSelecionada, $turmasDisponiveis, true)) {
            throw ValidationException::withMessages([
                'turma' => 'A turma selecionada não é válida para a atividade/ação escolhida.',
            ]);
        }

        $dados['turma'] = $turmaSelecionada;

        return $dados;
    }

    private function municipioDoUsuario(?User $user)
    {
        if (!$user) {
            return null;
        }

        return $user->participante()
            ->with('municipio.estado')
            ->first()
            ?->municipio;
    }

    private function garantirNaoEfetivado(Agendamento $agendamento): void
    {
        if (!$agendamento->efetivado) {
            return;
        }

        abort(403, 'Agendamentos efetivados não podem mais ser alterados ou excluídos.');
    }
}
