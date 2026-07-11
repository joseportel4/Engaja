<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Papel timbrado (Cartas para Esperançar)
    |--------------------------------------------------------------------------
    |
    | Modelo PDF (A5, 148x210mm) sobre o qual o texto digitado pelo voluntário
    | é sobreposto para gerar o documento final. As medidas (em mm) posicionam
    | o texto sobre as linhas pontilhadas do modelo — ajuste caso o modelo mude.
    |
    */

    'timbrado' => [
        'path' => public_path('images/cartas/PAEB_CartasparaEsperançar_PapeldeCarta.pdf'),

        // Área de escrita, em milímetros.
        'margin_left' => 13.0,
        'margin_right' => 13.0,
        'start_top' => 28.5,   // topo do texto da primeira linha
        'line_height' => 4.94, // igual ao espaçamento das linhas pontilhadas (14pt)
        'bottom_margin' => 46.0, // reserva o rodapé (logos/ilustração)
        'font_family' => 'Helvetica',
        'font_size' => 10.0,
    ],
];
