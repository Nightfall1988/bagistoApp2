<?php

namespace App\Services\Integration\MidOcean;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MidOceanProductMapperService extends BaseService
{
    protected ProductImportRepository $productImportRepository;

    public function __construct(ProductImportRepository $productImportRepository)
    {
        $this->productImportRepository = $productImportRepository;
    }


    public function mapProducts(): void
    {
        $this->mapVariantProducts(
            $this->mapParentProducts()
        );
    }

    private function mapParentProducts(): Collection
    {
        $parentProducts = collect($this->data)->map(function (array $row) {
            return [
                'sku'  => $row['master_code'],
                'type' => 'configurable',
            ];
        });
        $this->productImportRepository->upsertProducts($parentProducts);

        return $parentProducts;
    }

    private function mapVariantProducts(Collection $parentProductCollection): void
    {
        $parentProducts = $this->productImportRepository->getProducts($parentProductCollection);

        $variantProducts = collect($this->data)->flatMap(function (array $row) use ($parentProducts) {
            $parentId = $parentProducts[$row['master_code']]->id ?? null;

            return collect($row['variants'])->map(function ($variant) use ($parentId) {
                return [
                    'sku'       => $variant['sku'],
                    'type'      => 'simple',
                    'parent_id' => $parentId,
                ];
            });
        });

        $this->productImportRepository->upsertVariants($variantProducts);
    }

    private function getSKUCodesFromJson(): Collection
    {
        return collect($this->data)->flatMap(function ($item) {
            $itemSkus = [];

            $itemSkus[] = ['sku' => $item['master_code']];

            foreach ($item['variants'] as $variant) {
                $itemSkus[] = ['sku' => $variant['sku']];
            }

            return $itemSkus;
        });
    }

    protected const COMMON_ATR_MAP = [
        ['id'=> 30, 'code'=>'dimensions'],
        ['id'=> 25, 'code'=>'brand'],
        ['id'=> 19, 'code'=>'length'],
        ['id'=> 20, 'code'=>'width'],
        ['id'=> 21, 'code'=>'height'],
        ['id'=> 22, 'code'=>'net_weight'],
        ['id'=> 10, 'code'=>'short_description'],
        ['id'=> 29, 'code'=>'material'],
    ];

    protected const CONFIGURABLE_ATR_MAP = [
        ['id'=> 1, 'code'=>'master_code'],
    ];

    protected const VARIANT_ATR_MAP = [
        ['id'=> 1, 'code'=>'sku'],
        ['id'=> 23, 'code'=>'pms_color'],
    ];

    public function mapProductAttributeValues(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromJson());

        $productAttributes = collect($this->data)->flatMap(function ($item) use ($products) {
            $productAttributes = [];
            foreach (self::COMMON_ATR_MAP as $attribute) {
                if (! empty($item[$attribute['code']])) {
                    $this->mapConfigurableAttributeValue($productAttributes, $attribute, $products, $item);
                    $this->mapVariantAttributeValue($productAttributes, $attribute, $products, $item);
                }
            }
            foreach (self::CONFIGURABLE_ATR_MAP as $attribute) {
                if (! empty($item[$attribute['code']])) {
                    $this->mapConfigurableAttributeValue($productAttributes, $attribute, $products, $item);
                }
            }
            foreach (self::VARIANT_ATR_MAP as $attribute) {
                if (! empty($item[$attribute['code']])) {
                    $this->mapVariantAttributeValue($productAttributes, $attribute, $products, $item);
                }
            }

            return $productAttributes;
        })->filter();

        $this->productImportRepository->upsertProductAttributeValues($productAttributes);
    }

    private function mapConfigurableAttributeValue(array &$productAttributes, array $attribute, Collection $products, array $item): void
    {
        $productAttributes[] = [
            'attribute_id'  => $attribute['id'],
            'product_id'    => $products[$item['master_code']]->id,
            'text_value'    => $item[$attribute['code']],
            'channel'       => 'default',
            'locale'        => 'en',
            'unique_id'     => 'default|en|'.$products[$item['master_code']]->id.'|'.$attribute['id'],
        ];
    }

    private function mapVariantAttributeValue(array &$productAttributes, array $attribute, Collection $products, array $item): void
    {
        foreach ($item['variants'] as $variant) {
            $productAttributes[] = [
                'attribute_id'  => $attribute['id'],
                'product_id'    => $products[$variant['sku']]->id,
                'text_value'    => $item[$attribute['code']],
                'channel'       => 'default',
                'locale'        => 'en',
                'unique_id'     => 'default|en|'.$products[$variant['sku']]->id.'|'.$attribute['id'],
            ];
        }
    }

    protected const ATR_OPTIONS_MAP = [
        'dimensions'       => 30,
        'material'         => 29,
        'color_description'=> 23,
        'color_group'      => 23,
    ];

    public function mapAttributeOptions(): void
    {
        $attributeOptions = collect($this->data)->flatMap(function ($item) {
            $attributeOptions = [];

            if (! empty($item['dimensions'])) {
                $attributeOptions[] = [
                    'admin_name'   => $item['dimensions'],
                    'attribute_id' => self::ATR_OPTIONS_MAP['dimensions'],
                ];
            }

            if (! empty($item['material'])) {
                $attributeOptions[] = [
                    'admin_name'   => $item['material'],
                    'attribute_id' => self::ATR_OPTIONS_MAP['material'],
                ];
            }

            foreach ($item['variants'] as $variant) {
                if (! empty($variant['color_description'])) {
                    $attributeOptions[] = [
                        'admin_name'   => $variant['color_description'],
                        'attribute_id' => self::ATR_OPTIONS_MAP['color_description'],
                    ];
                }
                if (! empty($variant['color_group'])) {
                    $attributeOptions[] = [
                        'admin_name'   => $variant['color_group'],
                        'attribute_id' => self::ATR_OPTIONS_MAP['color_group'],
                    ];
                }
            }

            return $attributeOptions;
        })->filter();

        $this->productImportRepository->upsertAttributeOptions($attributeOptions);
    }

    public function mapProductCategories(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromJson());
        $categories = $this->productImportRepository->getCategories();

        $productCategories = collect($this->data)->flatMap(function ($row) use ($products, $categories) {
            $productCategories = [];

            //The parent category gets its categories from the first variants first two categories
            if (isset($row['variants'][0]['category_level1'])) {
                $productCategories[] = [
                    'product_id' => $products[$row['master_code']]->id,
                    'category_id'=> $categories[trim($row['variants'][0]['category_level1'])]->id,
                ];
            }
            if (isset($row['variants'][0]['category_level2'])) {
                $productCategories[] = [
                    'product_id' => $products[$row['master_code']]->id,
                    'category_id'=> $categories[trim($row['variants'][0]['category_level2'])]->id,
                ];
            }

            foreach ($row['variants'] as $variant) {
                if (isset($variant['category_level1'])) {
                    $productCategories[] = [
                        'product_id' => $products[$variant['sku']]->id,
                        'category_id'=> $categories[trim($variant['category_level1'])]->id,
                    ];
                }
                if (isset($variant['category_level2'])) {
                    $productCategories[] = [
                        'product_id' => $products[$variant['sku']]->id,
                        'category_id'=> $categories[trim($variant['category_level2'])]->id,
                    ];
                }
                if (isset($variant['category_level3'])) {
                    $productCategories[] = [
                        'product_id' => $products[$variant['sku']]->id,
                        'category_id'=> $categories[trim($variant['category_level3'])]->id,
                    ];
                }
            }

            return $productCategories;
        })->filter();

        $this->productImportRepository->upsertProductCategories($productCategories);
    }

    public function mapProductFlats(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromJson());

        $productFlats = collect($this->data)->flatMap(function (array $row) use ($products) {
            $flatProducts = [];

            $flatProducts[] = [
                'sku'                       => $row['master_code'],
                'type'                      => 'configurable',
                'product_number'            => $row['master_id'],
                'name'                      => $row['product_name'] ?? null,
                'short_description'         => '<p>'.($row['short_description'] ?? null).'</p>',
                'description'               => '<p>'.($row['long_description'] ?? null).'</p>',
                'weight'                    => $row['net_weight'],
                'url_key'                   => Str::slug((isset($row['product_name']) ? $row['product_name'].'-' : null).$row['master_code']),
                'meta_title'                => $row['product_class'],
                'meta_description'          => $row['short_description'],
                'product_id'                => $products[$row['master_code']]->id,
                'locale'                    => 'en',
                'new'                       => '1',
                'featured'                  => '1',
                'status'                    => '1',

            ];

            foreach ($row['variants'] as $variant) {
                $flatProducts[] = [
                    'sku'                       => $variant['sku'],
                    'type'                      => 'simple',
                    'product_number'            => $row['master_id'].'-'.$variant['sku'],
                    'name'                      => $row['product_name'] ?? null,
                    'short_description'         => '<p>'.($row['short_description'] ?? null).'</p>',
                    'description'               => '<p>'.($row['long_description'] ?? null).'</p>',
                    'url_key'                   => Str::slug((isset($row['product_name']) ? $row['product_name'].'-' : null).$variant['sku']),
                    'meta_title'                => $row['product_class'],
                    'meta_description'          => $row['short_description'],
                    'weight'                    => $row['net_weight'],
                    'product_id'                => $products[$variant['sku']]->id,
                    'locale'                    => 'en',
                    'new'                       => '1',
                    'featured'                  => '1',
                    'status'                    => '1',
                ];
            }

            return $flatProducts;
        });

        $this->productImportRepository->upsertProductFlats($productFlats);
    }

    public function mapProductImageURLs(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromJson());
        $productImageURLs = collect($this->data)->flatMap(function ($row) use ($products) {
            $imageURLs = [];

            $productId = $products[$row['master_code']]->id;
            $position = 1;
            if (isset($row['digital_assets'])) {
                foreach ($row['digital_assets'] as $digitalAsset) {
                    $imageURLs[] = [
                        'url'       => $digitalAsset['url'],
                        'product_id'=> $productId,
                        'position'  => $position,
                        'type'      => $digitalAsset['type'],
                    ];
                    $position++;
                }
            }
            if (isset($row['variants'])) {
                foreach ($row['variants'] as $variant) {
                    $position = 1;
                    $variantProductId = $products[$variant['sku']]->id;
                    if (isset($variant['digital_assets'])) {
                        foreach ($variant['digital_assets'] as $digitalAsset) {
                            $imageURLs[] = [
                                'url'       => $digitalAsset['url'],
                                'product_id'=> $variantProductId,
                                'position'  => $position,
                                'type'      => $digitalAsset['type'],
                            ];
                            $position++;
                        }
                    }
                }
            }

            return $imageURLs;
        });

        $this->productImportRepository->upsertProductURLImages($productImageURLs);
    }
}
