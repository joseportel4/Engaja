<?php

namespace App\Services;

use App\Models\Participante;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PresencaImportIdentityResolver
{
    private array $usersByEmail = [];

    private array $participantsByCpf = [];

    private array $indexedCpfs = [];

    private array $participantsById = [];

    private array $reservedEmails = [];

    public function prepare(array $rows): void
    {
        $this->usersByEmail = [];
        $this->participantsByCpf = [];
        $this->indexedCpfs = [];
        $this->participantsById = [];
        $emails = collect($rows)->pluck('email')->filter()->unique()->values()->all();
        $cpfs = collect($rows)->where('email', '')->pluck('cpf')->filter(fn ($cpf) => $cpf !== null && $cpf !== '')->unique()->values()->all();
        $this->reservedEmails = array_fill_keys($emails, true);

        foreach (array_chunk($emails, 1000) as $chunk) {
            $users = User::withTrashed()
                ->where('sistema_origem', User::SISTEMA_ENGAJA)
                ->whereIn(DB::raw('LOWER(TRIM(email))'), $chunk)
                ->with(['participante' => fn ($query) => $query->withTrashed()])
                ->orderByDesc('created_at')->orderByDesc('id')->get();

            foreach ($users as $user) {
                $email = mb_strtolower(trim($user->email));
                $this->usersByEmail[$email][] = $user;
                if ($user->participante) {
                    $user->participante->setRelation('user', $user);
                    $this->remember($user->participante);
                }
            }
        }

        foreach (array_chunk($cpfs, 1000) as $chunk) {
            $participants = Participante::withTrashed()
                ->whereHas('user', fn ($query) => $query->withTrashed()
                    ->where('sistema_origem', User::SISTEMA_ENGAJA))
                ->whereIn(DB::raw("regexp_replace(coalesce(cpf, ''), '[^0-9]', '', 'g')"), $chunk)
                ->with(['user' => fn ($query) => $query->withTrashed()])
                ->get();

            foreach ($participants as $participant) {
                if (! isset($this->participantsById[$participant->id])) {
                    $this->remember($participant);
                }
            }
        }
    }

    public function resolve(array $row): Participante
    {
        if ($row['email'] !== '') {
            $matches = $this->usersByEmail[$row['email']] ?? [];
            $active = array_values(array_filter($matches, fn (User $user) => ! $user->trashed()));

            if (count($active) > 1) {
                $this->fail($row, 'Há mais de um perfil com este e-mail. Regularize o cadastro.');
            }

            $user = $active[0] ?? $matches[0] ?? null;
            if ($user) {
                return $this->participantFor($user, $row);
            }
        } else {
            $matches = collect($this->participantsByCpf[$row['cpf']] ?? [])
                ->sort(function (Participante $a, Participante $b) {
                    return ($b->user->created_at?->getTimestamp() ?? 0)
                        <=> ($a->user->created_at?->getTimestamp() ?? 0)
                        ?: $b->user_id <=> $a->user_id
                        ?: $b->id <=> $a->id;
                });
            $participant = $matches->first(
                fn (Participante $item) => ! $item->trashed() && ! $item->user->trashed()
            ) ?? $matches->first();

            if ($participant) {
                $this->assertActive($participant->user, $participant, $row);

                return $participant;
            }
        }

        $email = $row['email'] !== '' ? $row['email'] : $this->fictitiousEmail($row);

        try {
            $user = User::create([
                'name' => $row['nome'],
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'sistema_origem' => User::SISTEMA_ENGAJA,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            $this->fail($row, 'O e-mail foi cadastrado durante a importação. Tente novamente.');
        }

        $this->usersByEmail[mb_strtolower($email)][] = $user;
        $this->reservedEmails[mb_strtolower($email)] = true;

        return $this->participantFor($user, $row);
    }

    public function remember(Participante $participant): void
    {
        $this->participantsById[$participant->id] = $participant;
        $cpf = preg_replace('/\D+/', '', (string) $participant->cpf);
        $previous = $this->indexedCpfs[$participant->id] ?? null;

        if ($previous !== null && $previous !== $cpf) {
            unset($this->participantsByCpf[$previous][$participant->id]);
        }

        $this->indexedCpfs[$participant->id] = $cpf;
        if ($cpf !== '') {
            $this->participantsByCpf[$cpf][$participant->id] = $participant;
        }
    }

    private function participantFor(User $user, array $row): Participante
    {
        $participant = $user->participante;
        if ($participant) {
            $participant = $this->participantsById[$participant->id] ?? $participant;
        }
        $this->assertActive($user, $participant, $row);
        $participant ??= Participante::firstOrCreate(['user_id' => $user->id]);
        $participant->setRelation('user', $user);
        $user->setRelation('participante', $participant);

        return $participant;
    }

    private function assertActive(User $user, ?Participante $participant, array $row): void
    {
        if ($user->trashed() || $participant?->trashed()) {
            $this->fail($row, 'O perfil encontrado está excluído. Regularize o cadastro antes de importar.');
        }
    }

    private function fictitiousEmail(array $row): string
    {
        $base = rtrim(substr(Str::slug($row['nome'], '.'), 0, 54), '.');
        if ($base === '') {
            $this->fail($row, 'O nome informado não permite gerar o e-mail fictício. Corrija o nome.');
        }

        $email = $base.'@ficticio.org.br';
        while (isset($this->reservedEmails[$email]) || User::withTrashed()
            ->where('sistema_origem', User::SISTEMA_ENGAJA)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()) {
            $email = $base.'.'.strtolower(Str::random(8)).'@ficticio.org.br';
        }

        return $email;
    }

    private function fail(array $row, string $message): never
    {
        throw ValidationException::withMessages([
            'rows' => PresencaImportValidator::location($row).': '.$message,
        ]);
    }
}
