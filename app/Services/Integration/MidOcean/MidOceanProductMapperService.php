<?php

namespace App\Services\Integration\MidOcean;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use Hitexis\Product\Models\Product;
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
        $attributeFamilyId = $this->productImportRepository->getDefaultAttributeFamily()->id;
        $parentProducts = collect($this->data)->map(function (array $row) use ($attributeFamilyId) {
            return [
                'sku'                 => $row['master_code'],
                'type'                => 'configurable',
                'attribute_family_id' => $attributeFamilyId,
            ];
        });
        $this->productImportRepository->upsertProducts($parentProducts);

        return $parentProducts;
    }

    private function mapVariantProducts(Collection $parentProductCollection): void
    {
        $parentProducts = $this->productImportRepository->getProducts($parentProductCollection);
        $attributeFamilyId = $this->productImportRepository->getDefaultAttributeFamily()->id;

        $variantProducts = collect($this->data)->flatMap(function (array $row) use ($parentProducts, $attributeFamilyId) {
            $parentId = $parentProducts[$row['master_code']]->id ?? null;

            return collect($row['variants'])->map(function ($variant) use ($parentId, $attributeFamilyId) {
                return [
                    'sku'                 => $variant['sku'],
                    'type'                => 'simple',
                    'parent_id'           => $parentId,
                    'attribute_family_id' => $attributeFamilyId,
                ];
            });
        });

        $this->productImportRepository->upsertVariants($variantProducts);
    }

    public function mapSupplierCodes(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromJson());
        $midOceanIdentifier = env('MIDOECAN_IDENTIFIER', 'midocean');

        $supplierCodes = collect($products)->map(function (Product $row) use ($midOceanIdentifier) {
            return [
                'product_id'    => $row->id,
                'supplier_code' => $midOceanIdentifier,
            ];
        });

        $this->productImportRepository->upsertSupplierCodes($supplierCodes);
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
        ['id'=> 9, 'code'=>'short_description'],
        ['id'=> 10, 'code'=>'long_description'],
        ['id'=> 29, 'code'=>'material'],
        ['id'=> 2, 'code'=>'product_name'],
    ];

    protected const CONFIGURABLE_ATR_MAP = [
        ['id'=> 1, 'code'=>'master_code'],
    ];

    protected const VARIANT_ATR_MAP = [
        ['id'=> 1, 'code'=>'sku'],
    ];

    public function mapProductAttributeValues(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromJson());
        $attributeOptions = $this->productImportRepository->getAttributeOptionsByName();

        $productAttributes = collect($this->data)->flatMap(function ($item) use ($products, $attributeOptions) {
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
                $this->mapVariantAttributeValue($productAttributes, $attribute, $products, $item, false);
            }

            $this->mapURLKeys($productAttributes, $products, $item);
            $this->mapColors($productAttributes, $products, $item, $attributeOptions);
            $this->mapSizes($productAttributes, $products, $item, $attributeOptions);
            $this->mapProductVisibilities($productAttributes, $products, $item);
            $this->mapProductStatuses($productAttributes, $products, $item);
            $this->mapProductNumbers($productAttributes, $products, $item);

            return $productAttributes;
        })->filter();

        $this->productImportRepository->upsertProductAttributeValues($productAttributes);
    }

    protected const URL_KEY_ATTRIBUTE_ID = 3;

    private function mapURLKeys(array &$productAttributes, Collection $products, array $item): void
    {
        $productAttributes[] = [
            'attribute_id'  => self::URL_KEY_ATTRIBUTE_ID,
            'product_id'    => $products[$item['master_code']]->id,
            'text_value'    => Str::slug((isset($item['product_name']) ? $item['product_name'].'-' : null).$item['master_code']),
            'integer_value' => null,
            'boolean_value' => null,
            'channel'       => 'default',
            'locale'        => 'en',
            'unique_id'     => 'default|en|'.$products[$item['master_code']]->id.'|'.self::URL_KEY_ATTRIBUTE_ID,
        ];
        foreach ($item['variants'] as $variant) {
            $productAttributes[] = [
                'attribute_id'  => self::URL_KEY_ATTRIBUTE_ID,
                'product_id'    => $products[$variant['sku']]->id,
                'text_value'    => Str::slug((isset($row['product_name']) ? $row['product_name'].'-' : null).$variant['sku']),
                'integer_value' => null,
                'boolean_value' => null,
                'channel'       => 'default',
                'locale'        => 'en',
                'unique_id'     => 'default|en|'.$products[$variant['sku']]->id.'|'.self::URL_KEY_ATTRIBUTE_ID,
            ];
        }
    }

    protected const COLOR_ATTRIBUTE_KEY = 23;

    private function mapColors(array &$productAttributes, Collection $products, array $item, Collection $attributeOptions): void
    {

        foreach ($item['variants'] as $variant) {
            $productAttributes[] = [
                'attribute_id'     => self::COLOR_ATTRIBUTE_KEY,
                'product_id'       => $products[$variant['sku']]->id,
                'integer_value'    => $attributeOptions[$variant['color_group']]->id,
                'text_value'       => null,
                'boolean_value'    => null,
                'channel'          => 'default',
                'locale'           => 'en',
                'unique_id'        => 'default|en|'.$products[$variant['sku']]->id.'|'.self::COLOR_ATTRIBUTE_KEY,
            ];
        }
    }

    protected const SIZE_ATTRIBUTE_KEY = 24;

    private function mapSizes(array &$productAttributes, Collection $products, array $item, Collection $attributeOptions): void
    {
        foreach ($item['variants'] as $variant) {
            if(isset($variant['size_textile'])){
                $productAttributes[] = [
                    'attribute_id'     => self::SIZE_ATTRIBUTE_KEY,
                    'product_id'       => $products[$variant['sku']]->id,
                    'integer_value'    => $attributeOptions[$variant['size_textile']]->id,
                    'text_value'       => null,
                    'boolean_value'    => null,
                    'channel'          => 'default',
                    'locale'           => 'en',
                    'unique_id'        => 'default|en|'.$products[$variant['sku']]->id.'|'.self::SIZE_ATTRIBUTE_KEY,
                ];
            }
        }
    }


    protected const PRODUCT_VISIBILITY_ATTRIBUTE_KEY = 7;

    private function mapProductVisibilities(array &$productAttributes, Collection $products, array $item): void
    {
        $hasSingleVariant = count($item['variants']) === 1;

        $productAttributes[] = [
            'attribute_id'  => self::PRODUCT_VISIBILITY_ATTRIBUTE_KEY,
            'product_id'    => $products[$item['master_code']]->id,
            'text_value'    => null,
            'integer_value' => null,
            'boolean_value' => $hasSingleVariant ? 0 : 1,
            'channel'       => 'default',
            'locale'        => 'en',
            'unique_id'     => 'default|en|'.$products[$item['master_code']]->id.'|'.self::PRODUCT_VISIBILITY_ATTRIBUTE_KEY,
        ];
        foreach ($item['variants'] as $variant) {
            $productAttributes[] = [
                'attribute_id'  => self::PRODUCT_VISIBILITY_ATTRIBUTE_KEY,
                'product_id'    => $products[$variant['sku']]->id,
                'text_value'    => null,
                'integer_value' => null,
                'boolean_value' => $hasSingleVariant ? 1 : 0,
                'channel'       => 'default',
                'locale'        => 'en',
                'unique_id'     => 'default|en|'.$products[$variant['sku']]->id.'|'.self::PRODUCT_VISIBILITY_ATTRIBUTE_KEY,
            ];
        }
    }

    protected const PRODUCT_STATUS_ATTRIBUTE_KEY = 8;

    private function mapProductStatuses(array &$productAttributes, Collection $products, array $item): void
    {
        $productAttributes[] = [
            'attribute_id'  => self::PRODUCT_STATUS_ATTRIBUTE_KEY,
            'product_id'    => $products[$item['master_code']]->id,
            'text_value'    => null,
            'integer_value' => null,
            'boolean_value' => 1,
            'channel'       => 'default',
            'locale'        => 'en',
            'unique_id'     => 'default|en|'.$products[$item['master_code']]->id.'|'.self::PRODUCT_STATUS_ATTRIBUTE_KEY,
        ];
        foreach ($item['variants'] as $variant) {
            $productAttributes[] = [
                'attribute_id'  => self::PRODUCT_STATUS_ATTRIBUTE_KEY,
                'product_id'    => $products[$variant['sku']]->id,
                'text_value'    => null,
                'integer_value' => null,
                'boolean_value' => 1,
                'channel'       => 'default',
                'locale'        => 'en',
                'unique_id'     => 'default|en|'.$products[$variant['sku']]->id.'|'.self::PRODUCT_STATUS_ATTRIBUTE_KEY,
            ];
        }
    }

    protected const PRODUCT_NUMBER_ATTRIBUTE_KEY = 27;

    private function mapProductNumbers(array &$productAttributes, Collection $products, array $item): void
    {
        $productAttributes[] = [
            'attribute_id'  => self::PRODUCT_NUMBER_ATTRIBUTE_KEY,
            'product_id'    => $products[$item['master_code']]->id,
            'text_value'    => $item['master_id'],
            'integer_value' => null,
            'boolean_value' => null,
            'channel'       => 'default',
            'locale'        => 'en',
            'unique_id'     => 'default|en|'.$products[$item['master_code']]->id.'|'.self::PRODUCT_NUMBER_ATTRIBUTE_KEY,
        ];
        foreach ($item['variants'] as $variant) {
            $productAttributes[] = [
                'attribute_id'  => self::PRODUCT_NUMBER_ATTRIBUTE_KEY,
                'product_id'    => $products[$variant['sku']]->id,
                'text_value'    => $item['master_id'].'-'.$variant['sku'],
                'integer_value' => null,
                'boolean_value' => null,
                'channel'       => 'default',
                'locale'        => 'en',
                'unique_id'     => 'default|en|'.$products[$variant['sku']]->id.'|'.self::PRODUCT_NUMBER_ATTRIBUTE_KEY,
            ];
        }
    }

    private function mapConfigurableAttributeValue(array &$productAttributes, array $attribute, Collection $products, array $item): void
    {
        $productAttributes[] = [
            'attribute_id'  => $attribute['id'],
            'product_id'    => $products[$item['master_code']]->id,
            'text_value'    => $item[$attribute['code']],
            'integer_value' => null,
            'boolean_value' => null,
            'channel'       => 'default',
            'locale'        => 'en',
            'unique_id'     => 'default|en|'.$products[$item['master_code']]->id.'|'.$attribute['id'],
        ];
    }

    private function mapVariantAttributeValue(array &$productAttributes, array $attribute, Collection $products, array $item, bool $commonAttribute = true): void
    {
        foreach ($item['variants'] as $variant) {
            $attributeValue = $commonAttribute ? $item[$attribute['code']] ?? null : $variant[$attribute['code']] ?? null;

            if (! empty($attributeValue)) {
                $productAttributes[] = [
                    'attribute_id'  => $attribute['id'],
                    'product_id'    => $products[$variant['sku']]->id,
                    'text_value'    => $attributeValue,
                    'integer_value' => null,
                    'boolean_value' => null,
                    'channel'       => 'default',
                    'locale'        => 'en',
                    'unique_id'     => 'default|en|'.$products[$variant['sku']]->id.'|'.$attribute['id'],
                ];
            }
        }
    }

    protected const ATR_OPTIONS_MAP = [
        'dimensions'       => 30,
        'material'         => 29,
        'color_description'=> 23,
        'color_group'      => 23,
        'size_textile'     => 24,
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

                if (! empty($variant['size_textile'])) {
                    $attributeOptions[] = [
                        'admin_name'   => $variant['size_textile'],
                        'attribute_id' => self::ATR_OPTIONS_MAP['size_textile'],
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
                    'category_id'=> $categories[trim($row['variants'][0]['category_level1'])]->category_id,
                ];
            }
            if (isset($row['variants'][0]['category_level2'])) {
                $productCategories[] = [
                    'product_id' => $products[$row['master_code']]->id,
                    'category_id'=> $categories[trim($row['variants'][0]['category_level2'])]->category_id,
                ];
            }

            foreach ($row['variants'] as $variant) {
                if (isset($variant['category_level1'])) {
                    $productCategories[] = [
                        'product_id' => $products[$variant['sku']]->id,
                        'category_id'=> $categories[trim($variant['category_level1'])]->category_id,
                    ];
                }
                if (isset($variant['category_level2'])) {
                    $productCategories[] = [
                        'product_id' => $products[$variant['sku']]->id,
                        'category_id'=> $categories[trim($variant['category_level2'])]->category_id,
                    ];
                }
                if (isset($variant['category_level3'])) {
                    $productCategories[] = [
                        'product_id' => $products[$variant['sku']]->id,
                        'category_id'=> $categories[trim($variant['category_level3'])]->category_id,
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

        $defaultChannelCode = $this->productImportRepository->getDefaultChannel()->code;
        $defaultAttributeFamilyId = $this->productImportRepository->getDefaultAttributeFamily()->id;

        $productFlats = collect($this->data)->flatMap(function (array $row) use ($products, $defaultChannelCode, $defaultAttributeFamilyId) {
            $flatProducts = [];

            $hasSingleVariant = count($row['variants']) === 1;

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
                'channel'                   => $defaultChannelCode,
                'attribute_family_id'       => $defaultAttributeFamilyId,
                'visible_individually'      => $hasSingleVariant ? 0 : 1,
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
                    'channel'                   => $defaultChannelCode,
                    'attribute_family_id'       => $defaultAttributeFamilyId,
                    'visible_individually'      => $hasSingleVariant ? 1 : 0,
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
            /*
            Mapping of documents, not needed for now
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
            }*/
            if (isset($row['variants'][0]['digital_assets'])) {
                $firstVariantImage = collect($row['variants'][0]['digital_assets'])->firstWhere('type', 'image');
                if ($firstVariantImage) {
                    $imageURLs[] = [
                        'url'       => $firstVariantImage['url'],
                        'product_id'=> $productId,
                        'position'  => 1,
                        'type'      => $firstVariantImage['type'],
                    ];
                }

                foreach ($row['variants'] as $variant) {
                    $variantProductId = $products[$variant['sku']]->id;
                    $position = 1;
                    if (isset($variant['digital_assets'])) {
                        foreach ($variant['digital_assets'] as $digitalAsset) {
                            if ($digitalAsset['type'] == 'image') {
                                $imageURLs[] = [
                                    'url'        => $digitalAsset['url'],
                                    'product_id' => $variantProductId,
                                    'position'   => $position,
                                    'type'       => $digitalAsset['type'],
                                ];
                                $position++;
                            }
                        }
                    }
                }
            }

            return $imageURLs;
        });

        $this->productImportRepository->upsertProductURLImages($productImageURLs);
    }
}
