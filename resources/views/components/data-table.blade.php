@props([
    'id',
    'columns' => [],
    'rows' => [],
    'pagination' => true,
    'pageSize' => 15,
    'rowSelection' => null,
    'domLayout' => 'autoHeight',
    'rowClassField' => null,
    'class' => '',
])

<div
    {{ $attributes->except('class') }}
    id="{{ $id }}"
    data-ag-grid
    class="{{ $class }}"
    data-columns="{{ json_encode($columns, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
    data-rows="{{ json_encode($rows, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
    data-pagination="{{ $pagination ? 'true' : 'false' }}"
    data-page-size="{{ $pageSize }}"
    @if ($rowSelection) data-row-selection="{{ $rowSelection }}" @endif
    data-dom-layout="{{ $domLayout }}"
    @if ($rowClassField) data-row-class-field="{{ $rowClassField }}" @endif
></div>
