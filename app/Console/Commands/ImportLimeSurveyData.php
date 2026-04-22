<?php

namespace App\Console\Commands;

use App\Services\LimeSurvey\LimeSurveyClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImportLimeSurveyData extends Command
{
    protected $signature = 'limesurvey:importar-dados
                            {--survey_id= : ID do survey específico (usa todos os ativos se omitido)}';

    protected $description = 'Importa dados do LimeSurvey para o cache (TTL de 24h). Usado pelo scheduler diário.';

    public function __construct(private readonly LimeSurveyClient $client)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $surveyIds = $this->resolveSurveyIds();

        if (empty($surveyIds)) {
            $this->warn('Nenhum survey ativo encontrado.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Importando %d survey(s) para o cache...', count($surveyIds)));

        $errors = 0;
        foreach ($surveyIds as $surveyId) {
            if (!$this->importSurvey($surveyId)) {
                $errors++;
            }
        }

        if ($errors > 0) {
            $this->warn(sprintf('%d survey(s) com erro durante a importação.', $errors));
            return self::FAILURE;
        }

        $this->info('Importação concluída com sucesso.');
        return self::SUCCESS;
    }

    private function resolveSurveyIds(): array
    {
        $surveyIdOption = $this->option('survey_id');

        if ($surveyIdOption !== null) {
            return [(int) $surveyIdOption];
        }

        $defaultId = (int) config('services.limesurvey.survey_id');

        try {
            $surveys = $this->client->listSurveys();
            $active = array_filter($surveys, fn (array $s) => ($s['active'] ?? '') === 'Y');
            $ids = array_values(array_map(fn (array $s) => (int) $s['sid'], $active));
            return $ids ?: ($defaultId > 0 ? [$defaultId] : []);
        } catch (\Throwable $e) {
            $this->error('Erro ao listar surveys: ' . $e->getMessage());
            return $defaultId > 0 ? [$defaultId] : [];
        }
    }

    private function importSurvey(int $surveyId): bool
    {
        $this->line("  Survey {$surveyId}...");

        try {
            $questions = $this->refreshCache(
                "limesurvey:{$surveyId}:questions",
                fn () => $this->client->listQuestions($surveyId)
            );
            $this->line("    ✓ Questões: " . count($questions));

            $responses = $this->refreshCache(
                "limesurvey:{$surveyId}:responses",
                fn () => $this->client->exportResponses($surveyId)
            );
            $this->line("    ✓ Respostas: " . count($responses));

            Cache::put("limesurvey:{$surveyId}:cached_at", now(), now()->addDay());

            $this->refreshAnswerOptions($surveyId, $questions);

            return true;
        } catch (\Throwable $e) {
            $this->error("    ✗ Erro no survey {$surveyId}: " . $e->getMessage());
            return false;
        }
    }

    private function refreshAnswerOptions(int $surveyId, array $questions): void
    {
        $lTypeQuestions = array_filter(
            $questions,
            fn (array $q) => strtoupper((string) ($q['type'] ?? '')) === 'L'
                && ($q['parent_qid'] ?? '0') === '0'
        );

        if (empty($lTypeQuestions)) {
            return;
        }

        $count = 0;
        foreach ($lTypeQuestions as $question) {
            $qid = (int) ($question['qid'] ?? 0);
            if ($qid <= 0) {
                continue;
            }

            try {
                $this->refreshCache(
                    "limesurvey:{$surveyId}:answer_options:{$qid}",
                    fn () => $this->client->getQuestionProperties($qid)
                );
                $count++;
            } catch (\Throwable $e) {
                $this->warn("    ⚠ Opções da questão {$qid}: " . $e->getMessage());
            }
        }

        if ($count > 0) {
            $this->line("    ✓ Opções de resposta: {$count} questão(ões) tipo L");
        }
    }

    private function refreshCache(string $key, \Closure $fetch): mixed
    {
        Cache::forget($key);
        $data = $fetch();
        Cache::put($key, $data, now()->addDay());
        return $data;
    }
}
