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
        'line_height' => 5.0,  // passo exato das linhas pontilhadas do modelo (medido)
        'bottom_margin' => 36.0, // reserva o rodapé; escreve até a última linha de largura cheia

        // Fonte manuscrita (Kalam) embutida via FPDF makefont (cp1252).
        'font_family' => 'Kalam',
        'font_dir' => resource_path('fonts/cartas'),
        'font_file' => 'Kalam-Regular.php',
        'font_size' => 11.0,
    ],
];
