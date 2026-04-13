<?php

use App\Http\Controllers\AutorizacaoImagemImportController;
use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\AgendamentoEfetivacaoController;
use App\Http\Controllers\AgendamentoParticipanteController;
use App\Http\Controllers\AtividadeAcaoController;
use App\Http\Controllers\AtividadeController;
use App\Http\Controllers\AvaliacaoAtividadeController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DimensaoController;
use App\Http\Controllers\EscalaController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\EvidenciaController;
use App\Http\Controllers\IndicadorController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\ModeloCertificadoController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\PresencaController;
use App\Http\Controllers\PresencaImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestaoController;
use App\Http\Controllers\RegiaoController;
use App\Http\Controllers\TemplateAvaliacaoController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'role:administrador|gerente|eq_pedagogica|articulador'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'home'])->middleware(['auth', 'verified'])->name('dashboard');
    Route::get('/dashboards/presencas', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboards.presencas');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->middleware(['auth', 'verified'])->name('dashboard.export');
    Route::get('/dashboards/avaliacoes', [DashboardController::class, 'avaliacoes'])->middleware(['auth', 'verified'])->name('dashboards.avaliacoes');
    Route::get('/dashboards/avaliacoes/dados', [DashboardController::class, 'avaliacoesData'])->middleware(['auth', 'verified'])->name('dashboards.avaliacoes.data');
    Route::get('/dashboards/bi', [DashboardController::class, 'bi'])->middleware(['auth', 'verified'])->name('dashboards.bi');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/profile/demographics', [ProfileController::class, 'completeDemographics'])->name('profile.complete-demographics');

    Route::post('/eventos/{evento}/inscrever', [InscricaoController::class, 'inscrever'])->name('inscricoes.inscrever');
    Route::delete('/eventos/{evento}/cancelar', [InscricaoController::class, 'cancelar'])->name('inscricoes.cancelar');

    Route::post('/atividades/{atividade}/presenca/checkin', [AtividadeController::class, 'checkin'])->name('atividades.presenca.checkin');
});

Route::middleware(['auth', 'permission:presenca.abrir'])->group(function () {
    Route::patch('/atividades/{atividade}/presenca/toggle', [AtividadeController::class, 'togglePresenca'])->name('atividades.presenca.toggle');
});

Route::middleware(['auth', 'role:administrador|gerente|eq_pedagogica|articulador'])->group(function () {
    Route::get('/atividades/{atividade}/presencas/import', [PresencaImportController::class, 'import'])->name('atividades.presencas.import');
    Route::post('/atividades/{atividade}/presencas/import', [PresencaImportController::class, 'cadastro'])->name('atividades.presencas.cadastro');

    Route::get('/atividades/{atividade}/presencas/preview', [PresencaImportController::class, 'preview'])->name('atividades.presencas.preview');
    Route::post('/atividades/{atividade}/presencas/savepage', [PresencaImportController::class, 'savePage'])->name('atividades.presencas.savepage');
    Route::post('/atividades/{atividade}/presencas/confirmar', [PresencaImportController::class, 'confirmar'])->name('atividades.presencas.confirmar');

    Route::post('/atividades/{atividade}/checklist', [AtividadeController::class, 'saveChecklist'])->name('atividades.checklist.save');

    Route::get('/meus-certificados', [ProfileController::class, 'certificados'])->name('profile.certificados');

    Route::get('/atividades/{atividade}/lista-presenca-pdf', [AtividadeController::class, 'downloadListaPresencaPdf'])
        ->name('atividades.lista-presenca.pdf');

    Route::get('/atividades/{atividade}/lista-autorizacao-pdf', [AtividadeController::class, 'downloadListaAutorizacaoImagemPdf'])
        ->name('atividades.lista-autorizacao.pdf');
});

Route::middleware(['auth', 'role:administrador|gerente|eq_pedagogica|articulador'])->group(function () {
    Route::get('/eventos/{evento}/inscricoes/import', [InscricaoController::class, 'import'])->name('inscricoes.import');
    Route::post('/eventos/{evento}/inscricoes/import', [InscricaoController::class, 'cadastro'])->name('inscricoes.cadastro');
    Route::get('/eventos/{evento}/inscricoes/import-moodle', [InscricaoController::class, 'moodleImport'])->name('inscricoes.moodle.import');
    Route::get('/eventos/{evento}/inscricoes/import-moodle/modelo-momentos', [InscricaoController::class, 'moodleMomentTemplateDownload'])->name('inscricoes.moodle.template.momentos');
    Route::post('/eventos/{evento}/inscricoes/import-moodle', [InscricaoController::class, 'moodleUpload'])->name('inscricoes.moodle.upload');
    Route::get('/eventos/{evento}/inscricoes/import-moodle/preview', [InscricaoController::class, 'moodlePreview'])->name('inscricoes.moodle.preview');
    Route::post('/eventos/{evento}/inscricoes/import-moodle/confirmar', [InscricaoController::class, 'moodleConfirm'])->name('inscricoes.moodle.confirm');
    Route::get('/eventos/{evento}/inscricoes/preview', [InscricaoController::class, 'preview'])->name('inscricoes.preview');
    Route::post('/eventos/{evento}/inscricoes/preview/save', [InscricaoController::class, 'savePage'])->name('inscricoes.preview.save');
    Route::post('/eventos/{evento}/inscricoes/confirmar', [InscricaoController::class, 'confirmar'])->name('inscricoes.confirmar');
    Route::get('/eventos/{evento}/inscricoes/selecionar', [InscricaoController::class, 'selecionar'])->name('inscricoes.selecionar');
    Route::post('/eventos/{evento}/inscricoes/selecionar', [InscricaoController::class, 'selecionarStore'])->name('inscricoes.selecionar.store');
});

Route::middleware(['auth', 'permission:inscricao.ver'])->group(function () {
    Route::get('/eventos/{evento}/inscritos', [InscricaoController::class, 'inscritos'])->name('inscricoes.inscritos');
});

Route::middleware(['auth'])->group(function () {
    // rotas que SME não pode acessar
    Route::middleware(['role:administrador|gerente|eq_pedagogica|articulador'])->group(function () {
        Route::resource('dimensaos', DimensaoController::class);
        Route::resource('indicadors', IndicadorController::class);
        Route::resource('evidencias', EvidenciaController::class);
        Route::resource('escalas', EscalaController::class);
        Route::resource('questaos', QuestaoController::class);
        Route::resource('templates-avaliacao', TemplateAvaliacaoController::class)
            ->parameters(['templates-avaliacao' => 'template']);
        Route::resource('avaliacoes', AvaliacaoController::class)
            ->parameters(['avaliacoes' => 'avaliacao']);
        Route::get('avaliacoes/{avaliacao}/respostas', [AvaliacaoController::class, 'respostas'])->name('avaliacoes.respostas');
        Route::get('avaliacoes/{avaliacao}/respostas/{submissao}', [AvaliacaoController::class, 'respostasMostrar'])->name('avaliacoes.respostas.mostrar');
        Route::get('atividades/{atividade}/avaliacoes', [AvaliacaoController::class, 'resultadosAtividade'])->name('atividades.avaliacoes');
        Route::get('atividades/{atividade}/avaliacoes/pdf', [AvaliacaoController::class, 'downloadResultadosPdf'])->name('atividades.avaliacoes.pdf');
        Route::get('agendamentos/efetivacoes', [AgendamentoEfetivacaoController::class, 'index'])->name('agendamentos.efetivacoes.index');
        Route::get('agendamentos/efetivados', [AgendamentoEfetivacaoController::class, 'efetivados'])->name('agendamentos.efetivados.index');
        Route::get('agendamentos/{agendamento}/efetivar', [AgendamentoEfetivacaoController::class, 'create'])->name('agendamentos.efetivacoes.create');
        Route::post('agendamentos/{agendamento}/efetivar/confirmar', [AgendamentoEfetivacaoController::class, 'confirm'])->name('agendamentos.efetivacoes.confirm');
        Route::post('agendamentos/{agendamento}/efetivar', [AgendamentoEfetivacaoController::class, 'store'])->name('agendamentos.efetivacoes.store');
    });

    // ROTAS ABAIXO SME PODEM ACESSAR, POR ISSO A DIVISÃO
    Route::middleware(['role:administrador|gerente|eq_pedagogica|articulador|SME'])->group(function () {
        Route::resource('agendamentos', AgendamentoController::class);
        Route::prefix('agendamentos/{agendamento}/participantes')
            ->name('agendamentos.participantes.')
            ->group(function () {
                Route::get('/', [AgendamentoParticipanteController::class, 'index'])->name('index');
                Route::get('/create', [AgendamentoParticipanteController::class, 'create'])->name('create');
                Route::post('/', [AgendamentoParticipanteController::class, 'store'])->name('store');
                Route::get('/{participante}/edit', [AgendamentoParticipanteController::class, 'edit'])->name('edit');
                Route::put('/{participante}', [AgendamentoParticipanteController::class, 'update'])->name('update');
                Route::delete('/{participante}', [AgendamentoParticipanteController::class, 'destroy'])->name('destroy');

                Route::get('/importar', [AgendamentoParticipanteController::class, 'import'])->name('import');
                Route::post('/importar', [AgendamentoParticipanteController::class, 'upload'])->name('upload');
                Route::get('/importar/preview', [AgendamentoParticipanteController::class, 'preview'])->name('import.preview');
                Route::post('/importar/confirmar', [AgendamentoParticipanteController::class, 'confirm'])->name('import.confirm');
            });
        Route::resource('atividade-acoes', AtividadeAcaoController::class)
            ->parameters(['atividade-acoes' => 'atividadeAcao']);
    });
});

Route::middleware(['auth', 'role:administrador|gerente'])
    ->prefix('certificados')
    ->name('certificados.')
    ->group(function () {
        Route::resource('modelos', ModeloCertificadoController::class)
            ->parameters(['modelos' => 'modelo']);
        Route::post('emitir/preparar', [CertificadoController::class, 'prepararEmissao'])->name('emitir.preparar');
        Route::get('emitir/preview-lista', [CertificadoController::class, 'previewLista'])->name('emitir.preview_lista');
        Route::post('emitir', [CertificadoController::class, 'emitir'])->name('emitir');
    });

Route::middleware(['auth', 'role:administrador'])
    ->group(function () {
        Route::resource('regioes', RegiaoController::class)
            ->parameters(['regioes' => 'regiao'])
            ->except(['create', 'edit', 'show']);
        Route::resource('estados', EstadoController::class)
            ->parameters(['estados' => 'estado'])
            ->except(['create', 'edit', 'show']);
        Route::resource('municipios', MunicipioController::class)
            ->parameters(['municipios' => 'municipio'])
            ->except(['create', 'edit', 'show']);
    });

Route::middleware(['auth', 'role:administrador|gerente|eq_pedagogica|articulador'])
    ->prefix('usuarios')
    ->name('usuarios.')
    ->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('cadastrar', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('verificar', [UserManagementController::class, 'verificarIndex'])->name('verificar.index');
        Route::post('verificar', [UserManagementController::class, 'verificarProcessar'])->name('verificar.processar');
        Route::get('verificar/exportar/{format}', [UserManagementController::class, 'verificarExportar'])->name('verificar.exportar');
        Route::get('{managedUser}/editar', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('{managedUser}', [UserManagementController::class, 'update'])->name('update');
        Route::post('certificados/emitir', [CertificadoController::class, 'emitirPorParticipantes'])->name('certificados.emitir');
        Route::get('exportar', [UserManagementController::class, 'export'])->name('export');
        Route::get('autorizacoes-imagem/importar', [AutorizacaoImagemImportController::class, 'import'])->name('autorizacoes.import');
        Route::post('autorizacoes-imagem/importar', [\App\Http\Controllers\AutorizacaoImagemImportController::class, 'upload'])->name('autorizacoes.upload');
        Route::get('autorizacoes-imagem/preview', [AutorizacaoImagemImportController::class, 'preview'])->name('autorizacoes.preview');
        Route::post('autorizacoes-imagem/confirmar', [AutorizacaoImagemImportController::class, 'confirmar'])->name('autorizacoes.confirmar');
    });

Route::middleware(['auth', 'role:administrador|gerente|eq_pedagogica|articulador'])->group(function () {
    Route::get('/eventos/{evento}/relatorios', [EventoController::class, 'relatorios'])
        ->name('eventos.relatorios');
});

Route::middleware(['auth'])->group(function () {
    Route::controller(AvaliacaoAtividadeController::class)
        ->prefix('atividades/{atividade}/relatorio')
        ->name('avaliacao-atividade.')
        ->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/edit', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
            Route::get('/pdf', 'downloadOwn')->name('download-own');
        });

    Route::get('/relatorios-avaliacao/{relatorio}/pdf', [AvaliacaoAtividadeController::class, 'download'])
        ->name('avaliacao-atividade.download');
});

Route::middleware(['auth', 'role:administrador|gerente'])->group(function () {
    Route::get('/relatorios-avaliacao', [AvaliacaoAtividadeController::class, 'index'])
        ->name('avaliacao-atividade.index');

    Route::get('/relatorios-avaliacao/{relatorio}', [AvaliacaoAtividadeController::class, 'show'])
        ->name('avaliacao-atividade.show');

    Route::get('/atividades/{atividade}/relatorios-avaliacao/pdf-consolidado', [AvaliacaoAtividadeController::class, 'baixarTodosPorAtividade'])
        ->name('avaliacao-atividade.download-all');
});

Route::middleware(['auth', 'role:administrador|gerente|eq_pedagogica|articulador'])->group(function () {
    Route::resource('eventos', EventoController::class);
    Route::get('eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');
    Route::get('eventos/{evento}/planejamento/pdf', [EventoController::class, 'gerarPdfPlanejamento'])->name('eventos.planejamento.pdf');
});

Route::resource('eventos.atividades', AtividadeController::class)
    ->parameters(['atividades' => 'atividade'])
    ->shallow();

Route::get('/eventos/{evento_id}/{atividade_id}/cadastro-e-inscricao', [EventoController::class, 'cadastro_inscricao'])->name('evento.cadastro_inscricao');
Route::post('/eventos/cadastro-e-inscricao/store', [EventoController::class, 'store_cadastro_inscricao'])->name('evento.store_cadastro_inscricao');

Route::get('/presenca/{atividade}/confirmar', [PresencaController::class, 'confirmarPresenca'])->name('presenca.confirmar');
Route::post('/presenca/{atividade}/confirmar', [PresencaController::class, 'store'])->name('presenca.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/meus-certificados', [ProfileController::class, 'certificados'])->name('profile.certificados');
    Route::get('/certificados/preview', [CertificadoController::class, 'preview'])->name('certificados.preview');
    Route::get('/certificados/{certificado}', [CertificadoController::class, 'show'])
        ->whereNumber('certificado')
        ->name('certificados.show');
    Route::get('/certificados/{certificado}/download', [CertificadoController::class, 'download'])
        ->whereNumber('certificado')
        ->name('certificados.download');
    Route::get('/minhas-presencas', [ProfileController::class, 'presencas'])->name('profile.presencas');
});

Route::middleware(['auth', 'role:administrador|gerente'])->group(function () {
    Route::get('/certificados/emitidos', [CertificadoController::class, 'emitidos'])->name('certificados.emitidos');
    Route::get('/certificados/{certificado}/edit', [CertificadoController::class, 'edit'])
        ->whereNumber('certificado')
        ->name('certificados.edit');
    Route::put('/certificados/{certificado}', [CertificadoController::class, 'update'])
        ->whereNumber('certificado')
        ->name('certificados.update');
});

Route::get('/formulario-avaliacao/{avaliacao}', [AvaliacaoController::class, 'formularioAvaliacao'])->name('avaliacao.formulario');
Route::post('/formulario-avaliacao/{avaliacao}', [AvaliacaoController::class, 'responderFormulario'])->name('avaliacao.formulario.responder');
Route::get('/validacao/{codigo}', [CertificadoController::class, 'validar'])->name('certificados.validacao');

require __DIR__.'/auth.php';
