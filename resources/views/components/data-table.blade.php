@props([
    'id',
    'columns' => [],
    'rows' => [],
    'pagination' => true,
    'pageSize' => 15,
    'rowSelection' => null,
    'domLayout' => 'autoHeight',
    'rowClassField' => null,
    'idField' => 'id',
    'selectedIds' => [],
    'rowSelectableField' => null,
    'detailRowField' => null,
    'detailRowHeight' => 420,
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
    @if ($rowSelection || $detailRowField) data-id-field="{{ $idField }}" @endif
    @if ($rowSelection && count($selectedIds)) data-selected-ids="{{ json_encode($selectedIds, JSON_HEX_APOS | JSON_HEX_QUOT) }}" @endif
    @if ($rowSelection && $rowSelectableField) data-row-selectable-field="{{ $rowSelectableField }}" @endif
    @if ($detailRowField) data-detail-row-field="{{ $detailRowField }}" data-detail-row-height="{{ $detailRowHeight }}" @endif
></div>
