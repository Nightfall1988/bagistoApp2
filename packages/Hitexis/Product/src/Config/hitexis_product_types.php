<?php

return [
    'simple'       => [
        'key'   => 'simple',
        'name'  => 'product::app.type.simple',
        'class' => 'Hitexis\Product\Type\Simple',
        'sort'  => 1,
    ],

    'configurable' => [
        'key'   => 'configurable',
        'name'  => 'product::app.type.configurable',
        'class' => 'Hitexis\Product\Type\Configurable',
        'sort'  => 2,
    ],

    'virtual'      => [
        'key'   => 'virtual',
        'name'  => 'product::app.type.virtual',
        'class' => 'Hitexis\Product\Type\Virtual',
        'sort'  => 3,
    ],

    'grouped'      => [
        'key'   => 'grouped',
        'name'  => 'product::app.type.grouped',
        'class' => 'Hitexis\Product\Type\Grouped',
        'sort'  => 4,
    ],

    'downloadable' => [
        'key'   => 'downloadable',
        'name'  => 'product::app.type.downloadable',
        'class' => 'Hitexis\Product\Type\Downloadable',
        'sort'  => 5,
    ],

    'bundle'       => [
        'key'   => 'bundle',
        'name'  => 'product::app.type.bundle',
        'class' => 'Hitexis\Product\Type\Bundle',
        'sort'  => 6,
    ],
];
