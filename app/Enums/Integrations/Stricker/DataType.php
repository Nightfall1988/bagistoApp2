<?php

namespace App\Enums\Integrations\Stricker;

enum DataType: string
{
    case PRODUCTS = 'products';
    case PRINT_DATA = 'print_data';
    case OPTIONALS = 'optionals';
    case IMAGES = 'images';

    /**
     * Get the data type corresponding to the given cache key.
     *
     * This method maps a given cache key to its corresponding data type.
     * It is useful when you need to determine the data type based on the cache key value.
     *
     * @param CacheKey $cacheKey The cache key.
     * @return DataType The corresponding data type.
     */
    public static function getDataTypeFromCacheKey(CacheKey $cacheKey): self
    {
        return match ($cacheKey) {
            CacheKey::PRODUCTS => self::PRODUCTS,
            CacheKey::PRINT_DATA => self::PRINT_DATA,
            CacheKey::OPTIONALS => self::OPTIONALS,
            CacheKey::IMAGES => self::IMAGES,
        };
    }
}
