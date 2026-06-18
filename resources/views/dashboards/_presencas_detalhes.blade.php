<div class="px-3 py-2 border-bottom small text-muted">
    Inscritos: <strong>{{ $inscritosCount }}</strong> |
    Presentes: <strong>{{ $presentesCount }}</strong> |
    Ausentes: <strong>{{ $ausentesCount }}</strong>
</div>

<div id="pres-{{ $atividade->id }}-presentes" class="p-2">
    <div class="section-title fw-bold">Presentes</div>
    @if($presentes->isEmpty())
        <div class="text-muted small p-3">Nenhuma presença registrada.</div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-primary">
                    <tr>
                        <th style="width: 35%;">Nome</th>
                        <th style="width: 25%;">E-mail</th>
                        <th style="width: 15%;">CPF</th>
                        <th style="width: 13%;">Vínculo</th>
                        <th style="width: 12%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($presentes as $p)
                        @php
                            $insc = optional($p->inscricao);
                            $part = optional($insc->participante);
                            $user = optional($part->user);
                            $statusLabel = ($insc->ouvinte ?? false) ? 'Ouvinte' : 'Presente';
                        @endphp
                        <tr>
                            <td>{{ $user->name ?? 'Participante #'.$part->id }}</td>
                            <td>{{ $user->email ?? '-' }}</td>
                            <td>{{ $part->cpf ?: '-' }}</td>
                            <td>{{ $part->tag ?: '-' }}</td>
                            <td>{{ $statusLabel }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div id="pres-{{ $atividade->id }}-ausentes" class="p-2">
    <div class="section-title fw-bold">Ausentes</div>
    @if($ausentes->isEmpty())
        <div class="text-muted small p-3">Nenhum ausente registrado.</div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th style="width: 35%;">Nome</th>
                        <th style="width: 30%;">E-mail</th>
                        <th style="width: 18%;">CPF</th>
                        <th style="width: 17%;">Vínculo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ausentes as $insc)
                        @php
                            $part = optional($insc->participante);
                            $user = optional($part->user);
                        @endphp
                        <tr>
                            <td>{{ $user->name ?? 'Participante #'.$part->id }}</td>
                            <td>{{ $user->email ?? '-' }}</td>
                            <td>{{ $part->cpf ?: '-' }}</td>
                            <td>{{ $part->tag ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
