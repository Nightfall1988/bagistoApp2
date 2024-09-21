<?php

namespace App\Enums\Integrations\Midocean;

enum CacheKey: string
{
    case PRODUCTS = '_midocean_products';
    case PRODUCT_PRICES = '_midocean_product_prices';
    case PRINT_DATA = '_midocean_print_data';
    case PRINT_PRICES = '_midocean_print_prices';

    /**
     * Get the cache key corresponding to the given data type.
     *
     * This method maps a given data type to its corresponding cache key.
     * It is useful when you need to determine the cache key based on the data type value.
     *
     * @param DataType $dataType The data type.
     * @return CacheKey The corresponding cache key.
     */
    public static function getCacheKeyFromDataType(DataType $dataType): self
    {
        return match ($dataType) {
            DataType::PRODUCTS => self::PRODUCTS,
            DataType::PRODUCT_PRICES => self::PRODUCT_PRICES,
            DataType::PRINT_DATA => self::PRINT_DATA,
            DataType::PRINT_PRICES => self::PRINT_PRICES,
        };
    }
}