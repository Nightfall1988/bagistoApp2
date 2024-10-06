<?php

use App\Enums\Integrations\Midocean\DataType as MidoceanType;
use App\Enums\Integrations\Stricker\DataType as StrickerType;
use App\Enums\Integrations\XDConnect\DataType as XDType;

return [
    'xd_connect' => [
        'endpoints' => [
            XDType::PRODUCTS->value       => env('XDCONNECT_BASE_API').env('XDCONNECT_PRODUCT_ENDPOINT'),
            XDType::PRODUCT_PRICES->value => env('XDCONNECT_BASE_API').env('XDCONNECT_PRODUCT_PRICES_ENDPOINT'),
            XDType::PRINT_DATA->value     => env('XDCONNECT_BASE_API').env('XDCONNECT_PRINT_DATA_ENDPOINT'),
            XDType::PRINT_PRICES->value   => env('XDCONNECT_BASE_API').env('XDCONNECT_PRINT_PRICES_ENDPOINT'),
            XDType::STOCK->value          => env('XDCONNECT_BASE_API').env('XDCONNECT_STOCK_ENDPOINT'),
        ],
        'identifier' => env('XDCONNECT_IDENTIFIER'),
    ],
    'stricker' => [
        'endpoints' => [
            StrickerType::PRODUCTS->value        => env('STRICKER_BASE_API').env('STRICKER_PRODUCT_ENDPOINT'),
            StrickerType::PRINT_DATA->value      => env('STRICKER_BASE_API').env('STRICKER_PRINT_DATA_ENDPOINT'),
            StrickerType::OPTIONALS->value       => env('STRICKER_BASE_API').env('STRICKER_OPTIONALS_ENDPOINT'),
            StrickerType::IMAGES->value          => env('STRICKER_BASE_API').env('STRICKER_IMAGES_ENDPOINT'),
        ],
        'auth' => [
            'token' => env('STRICKER_AUTH_TOKEN'),
            'url'   => env('STRICKER_BASE_API').env('STRICKER_AUTH_URL'),
        ],
        'identifier' => env('STRICKER_IDENTIFIER'),
    ],
    'midocean' => [
        'endpoints' => [
            MidoceanType::PRODUCTS->value       => env('MIDOCEAN_BASE_API').env('MIDOECAN_PRODUCT_ENDPOINT'),
            MidoceanType::PRODUCT_PRICES->value => env('MIDOCEAN_BASE_API').env('MIDOECAN_PRODUCT_PRICES_ENDPOINT'),
            MidoceanType::PRINT_DATA->value     => env('MIDOCEAN_BASE_API').env('MIDOCEAN_PRINT_DATA_ENDPOINT'),
            MidoceanType::PRINT_PRICES->value   => env('MIDOCEAN_BASE_API').env('MIDOECAN_PRINT_PRICES_ENDPOINT'),
            MidoceanType::STOCK->value          => env('MIDOCEAN_BASE_API').env('MIDOECAN_STOCK_ENDPOINT'),
        ],
        'auth' => [
            'api-key' => env('MIDOECAN_API_KEY'),
        ],
        'identifier' => env('MIDOECAN_IDENTIFIER'),
    ],
];
