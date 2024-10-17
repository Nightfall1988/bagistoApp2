<?php

namespace App\Repositories\Population;

use App\Repositories\BaseImportRepository;
use Hitexis\PrintCalculator\Models\ProductUrlImages;
use Hitexis\Product\Models\Product;
use Hitexis\Product\Models\ProductAttributeValue;
use Hitexis\Product\Models\ProductFlat;
use Hitexis\Product\Models\ProductImage;
use Hitexis\Product\Models\ProductSuperAttribute;
use Illuminate\Support\Collection;

class PopulationRepository extends BaseImportRepository
{
    public function getConfigurableProductsWithAttributeValues(): Collection
    {
        return Product::where('type', 'configurable')->with('variants.attribute_values')->get();
    }

    public function getEnLocaleProductFlatsWithNoLvCounterParts(): Collection
    {
        return ProductFlat::from('product_flat as pf_en')
            ->leftJoin('product_flat as pf_lv', function ($join) {
                $join->on('pf_en.sku', '=', 'pf_lv.sku')
                    ->where('pf_lv.locale', 'lv');
            })
            ->where('pf_en.locale', 'en')
            ->whereNull('pf_lv.sku')
            ->select('pf_en.*')
            ->get();
    }

    public function getEnLocaleProductAttributesWithNoLvCounterParts(): Collection
    {
        return ProductAttributeValue::from('product_attribute_values as pav_en')
            ->leftJoin('product_attribute_values as pav_lv', function ($join) {
                $join->on('pav_en.product_id', '=', 'pav_lv.product_id')
                    ->on('pav_en.attribute_id', '=', 'pav_lv.attribute_id')
                    ->where('pav_lv.locale', 'lv');
            })
            ->where('pav_en.locale', 'en')
            ->whereNull('pav_lv.product_id')
            ->select('pav_en.*')
            ->get();
    }

    public function upsertSuperAttributeValues(Collection $attributeValues): void
    {
        $this->handleUpsert(function () use ($attributeValues) {
            $attributeValues->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductSuperAttribute::upsert(
                    $chunk->all(),
                    ['product_id', 'attribute_id'],
                );
            });
        }
        );
    }

    public function upsertProductFlatDuplicates(Collection $productFlats): void
    {
        $this->handleUpsert(function () use ($productFlats) {
            $productFlats->chunk(env('UPSERT_PRODUCT_FLAT_DUPLICATE_BATCH_SIZE', 100))->each(function (Collection $chunk) {
                ProductFlat::upsert(
                    $chunk->all(),
                    ['product_id', 'channel', 'locale'],
                    [
                        'sku',
                        'type',
                        'product_number',
                        'name',
                        'short_description',
                        'description',
                        'weight',
                        'url_key',
                        'meta_title',
                        'meta_description',
                        'attribute_family_id',
                        'visible_individually',
                        'new',
                        'featured',
                        'status',
                        'price',
                        'special_price',
                        'special_price_from',
                        'special_price_to',
                        'parent_id',
                    ]
                );

            });
        });
    }

    public function upsertProductAttributeValueDuplicates(Collection $productAttributeValues): void
    {
        $this->handleUpsert(function () use ($productAttributeValues) {
            $productAttributeValues->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductAttributeValue::upsert(
                    $chunk->all(),
                    ['product_id', 'attribute_id'],
                    [
                        'text_value',
                        'channel',
                        'locale',
                        'integer_value',
                        'boolean_value',
                        'float_value',
                        'datetime_value',
                        'date_value',
                        'json_value',
                    ],
                );

            });
        });
    }

    public function upsertProductImages(array $productImages): void
    {
        ProductImage::upsert(
            $productImages,
            ['product_id', 'position'],
            ['type', 'path', 'downloaded_from_url']
        );
    }

    public function getDownloadableProductUrlImages(): Collection
    {
        return ProductUrlImages::leftJoin('product_images', function ($join) {
            $join->on('product_url_images.product_id', '=', 'product_images.product_id')
                ->on('product_url_images.position', '=', 'product_images.position');
        })
            ->whereNull('product_images.id')
            ->orWhere(function ($query) {
                $query->whereColumn('product_url_images.url', '!=', 'product_images.downloaded_from_url');
            })
            ->select('product_url_images.*')
            ->get();
    }
}
