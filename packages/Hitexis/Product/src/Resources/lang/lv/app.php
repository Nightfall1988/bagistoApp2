<?php

return [
    'checkout' => [
        'cart' => [
            'integrity' => [
                'qty-missing'   => 'Vismaz vienam produktam jābūt vairāk nekā 1 daudzumam.',
            ],

            'inventory-warning' => 'Pieprasītais daudzums nav pieejams, lūdzu, vēlāk mēģiniet vēlreiz.',
            'missing-links'     => 'Šim produktam trūkst lejupielādējamo saišu.',
            'missing-options'   => 'Šim produktam trūkst iespēju.',
        ],
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'kopija-:value',
        'copy-of'                       => 'Kopija :value',
        'variant-already-exist-message' => 'Variants ar tādām pašām atribūtu opcijām jau pastāv.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Šī tipa prece :type nevar būt kopēta',
    ],

    'sort-by'  => [
        'options' => [
            'cheapest-first'  => 'No mazākās cenas',
            'expensive-first' => 'No lielākās cenas',
            'from-a-z'        => 'No A-Z',
            'from-z-a'        => 'No Z-A',
            'latest-first'    => 'Jaunākie pirmie',
            'oldest-first'    => 'Vecākie pirmie',
        ],
    ],

    'type'     => [
        'abstract'     => [
            'offers' => 'Pirkt :qty for :price katru un ietaupi :discount',
        ],

        'bundle'       => 'Paka',
        'configurable' => 'Konfigurējams',
        'downloadable' => 'Lejupielādējams',
        'grouped'      => 'Sagrupēts',
        'simple'       => 'Vienkārši',
        'virtual'      => 'Virtuāls',
    ],
];
