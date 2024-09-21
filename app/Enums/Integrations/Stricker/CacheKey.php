<?php

namespace App\Enums\Integrations\Stricker;

enum CacheKey: string
{
    case PRODUCTS = '_striker_products';
    case PRINT_DATA = '_striker_print_data';
    case OPTIONALS = '_striker_optionals';
    case IMAGES = '_striker_images';

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
            DataType::PRINT_DATA => self::PRINT_DATA,
            DataType::OPTIONALS => self::OPTIONALS,
            DataType::IMAGES => self::IMAGES,
        };
    }
}
