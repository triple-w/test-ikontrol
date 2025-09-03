<?php

return [
    // Impuestos disponibles y tasas por tipo
    // Nota: tasas expresadas en porcentaje (ej. 16 = 16%)
    'trasladado' => [
        'IVA'  => [0, 8, 16],
        'IEPS' => [8, 26.5],
    ],
    'retencion' => [
        'IVA' => [4],
        'ISR' => [10],
        'IEPS' => [],
    ],
];

