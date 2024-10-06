<?php

namespace App\Enums\Integrations\Midocean;

enum DataType: string
{
    case PRODUCTS = 'products';
    case PRODUCT_PRICES = 'product_prices';
    case PRINT_PRICES = 'print_prices';
    case PRINT_DATA = 'print_data';
    case STOCK = 'stock';

    /**
     * Get the data type corresponding to the given cache key.
     *
     * This method maps a given cache key to its corresponding data type.
     * It is useful when you need to determine the data type based on the cache key value.
     *
     * @param  CacheKey  $cacheKey  The cache key.
     * @return DataType The corresponding data type.
     */
    public static function getDataTypeFromCacheKey(CacheKey $cacheKey): self
    {
        return match ($cacheKey) {
            CacheKey::PRODUCTS       => self::PRODUCTS,
            CacheKey::PRODUCT_PRICES => self::PRODUCT_PRICES,
            CacheKey::PRINT_DATA     => self::PRINT_DATA,
            CacheKey::PRINT_PRICES   => self::PRINT_PRICES,
            CacheKey::STOCK          => self::STOCK,
        };
    }
}
