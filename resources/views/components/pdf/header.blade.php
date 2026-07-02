@props([
    'title',
    'subtitle' => null,
    'meta' => [],
])

@php
    /** @var array<int, string> $metaItens */
    $metaItens = array_values(array_filter((array) $meta, fn ($item) => filled($item)));
    $geradoPor = auth()->user()?->name;
@endphp

<div class="pdf-header">
    <h1 class="pdf-header__title">{{ $title }}</h1>
    @if($subtitle)
        <div class="pdf-header__subtitle">{{ $subtitle }}</div>
    @endif
    <div class="pdf-header__stamp">
        Gerado em {{ now()->format('d/m/Y \à\s H:i') }}@if($geradoPor) · por {{ $geradoPor }}@endif
    </div>

    @if(count($metaItens) > 0)
        <ul class="pdf-header__meta">
            @foreach($metaItens as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif
</div>
