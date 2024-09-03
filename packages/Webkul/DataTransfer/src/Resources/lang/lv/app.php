<?php

return [
    'importers' => [
        'customers' => [
            'title' => 'Customers',

            'validation' => [
                'errors' => [
                    'duplicate-email'        => 'Epasts : \'%s\' importa failā ir atrodams vairāk nekā vienu reizi.',
                    'duplicate-phone'        => 'Tālrunis : \'%s\' importa failā ir atrodams vairāk nekā vienu reizi.',
                    'email-not-found'        => 'Epasts : \'%s\' nav atrodams sistēmā.',
                    'invalid-customer-group' => 'Klientu grupa ir nederīga vai netiek atbalstīta',
                ],
            ],
        ],

        'products' => [
            'title' => 'Preces',

            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL atslēga: \'%s\' jau tika izveidots precei ar SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Nederīga vērtība atribūta grupas kolonnā (atribūta grupa neeksistē?)',
                    'invalid-type'              => 'Produkta tips ir nederīgs vai netiek atbalstīts',
                    'sku-not-found'             => 'Produkts ar norādīto SKU nav atrasts',
                    'super-attribute-not-found' => 'Super atribūts ar kodu: \'%s\' nav atrasts vai nepieder atribūtu saimei: \'%s\'',
                ],
            ],
        ],

        'tax-rates' => [
            'title' => 'Nodokļu likmes',

            'validation' => [
                'errors' => [
                    'duplicate-identifier' => 'Identifikators : \'%s\' importa failā ir atrodams vairāk nekā vienu reizi.',
                    'identifier-not-found' => 'Identifikators : \'%s\' nav atrodams sistēmā.',
                ],
            ],
        ],
    ],

    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolonnu skaits "%s" ir tukšas galvenes',
            'column-name-invalid'  => 'Nederīgi kolonnu nosaukumi: "%s".',
            'column-not-found'     => 'Nepieciešamās kolonnas nav atrastas: %s.',
            'column-numbers'       => 'Kolonnu skaits neatbilst rindu skaitam galvenē.',
            'invalid-attribute'    => 'galvenē ir nederīgs(-i) atribūts(-i): "%s".',
            'system'               => 'Radās neparedzēta sistēmas kļūda.',
            'wrong-quotes'         => 'Taisno pēdiņu vietā tiek lietotas cirtainās pēdiņas.',
        ],
    ],
];
