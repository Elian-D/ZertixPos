<?php

// Catálogo estático de impuestos — reemplaza el modelo Impuesto (tasa única,
// global, guardada en BD) por un set fijo de tasas reales de la DGII. Cada
// producto se asigna a uno o más de estos vía el pivote product_taxes
// (muchos-a-muchos, apilable — ver docs/features/v1.2.0.md Fase 5, REQ-5.1).
// Vive en config y no en BD porque estas tasas las fija la ley, no el usuario
// del sistema — igual que config/impuestos.php no es un CRUD, config/days.php
// tampoco lo es.
return [
    // ITBIS: mutuamente excluyentes (regla DGII — un producto no puede ser Exento
    // y estar gravado al 18% a la vez). 'group' => 'itbis' es lo que la UI usa
    // para renderizar estos como radio buttons en vez de checkboxes (Fase 5,
    // REQ-5.4 extra). Un producto siempre tiene EXACTAMENTE uno de estos —
    // incluyendo la opción "Sin ITBIS" (sin clave real, value="" en el radio,
    // filtrada antes de guardar) para el caso legítimo de no aplicar ninguno.
    'itbis_18' => [
        'label' => 'ITBIS 18%',
        'rate' => 18,
        'scope' => 'product',
        'group' => 'itbis',
    ],
    'itbis_16' => [
        'label' => 'ITBIS 16%',
        'rate' => 16,
        'scope' => 'product',
        'group' => 'itbis',
    ],
    'exento' => [
        'label' => 'Exento',
        'rate' => 0,
        'scope' => 'product',
        'group' => 'itbis',
    ],

    // 'itbis_0' => [
    //     'label' => 'Tasa 0%',
    //     'rate' => 0,
    //     'scope' => 'product',
    //     'group' => 'itbis',
    // ],
    // Comentado: distinto de 'exento' aunque la tasa numérica sea igual — en la
    // DGII son dos categorías legales distintas (Exento: bienes exentos, Art.
    // 343 del Código Tributario; Tasa 0%: exportaciones, Art. 342). El público
    // actual de ZertixPOS no exporta, así que no hay caso de uso real todavía —
    // queda declarado (y ya contemplado en el grupo 'itbis' de radio buttons)
    // para no tener que rediseñar el grupo el día que haga falta.

    // Aditivos (no excluyentes entre sí ni con el ITBIS elegido arriba) — sin
    // 'group', la UI los renderiza como checkboxes normales, apilables.
    'isc_telecom' => [
        'label' => 'ISC 10%',
        'rate' => 10,
        'scope' => 'product',
    ],

    // 'cdt' => [
    //     'label' => 'CDT 2%',
    //     'rate' => 2,
    //     'scope' => 'product',
    // ],
    // Comentado: Contribución al Desarrollo de las Telecomunicaciones (Ley
    // 153-98) — aplica a internet/cable/telefonía. El público actual de
    // ZertixPOS no vende servicios de telecomunicaciones, sin caso de uso real
    // todavía; queda declarada como checkbox aditivo, lista para descomentar.

    // scope 'sale' = se aplica sobre la venta completa, no por producto (ver
    // config/modules.php, entrada comentada 'sales.propina_legal', y
    // docs/features/v1.2.0.md Fase 5, REQ-5.7). Diferido: nada en el código
    // lee esta clave todavía, queda declarada para no reabrir la duda de
    // dónde vive la tasa el día que se retome.
    'propina_legal' => [
        'label' => 'Legal 10%',
        'rate' => 10,
        'scope' => 'sale',
    ],

    'default' => null, // el que se preselecciona/asigna al crear un producto nuevo
];
