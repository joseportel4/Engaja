<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Exportação de PDF dos dashboards
    |--------------------------------------------------------------------------
    |
    | Limites de segurança para a geração de PDFs extensos (ex.: dashboard de
    | presenças). O teto de atividades evita estouro de memória e timeout do
    | Browsershot/Chromium ao gerar relatórios sem filtro; quando excedido, o
    | PDF é truncado e exibe um aviso.
    |
    */

    'pdf' => [
        'max_atividades' => (int) env('DASHBOARD_PDF_MAX_ATIVIDADES', 200),
        'memory_limit' => env('DASHBOARD_PDF_MEMORY_LIMIT', '512M'),
        'timeout' => (int) env('DASHBOARD_PDF_TIMEOUT', 120),
    ],

];
