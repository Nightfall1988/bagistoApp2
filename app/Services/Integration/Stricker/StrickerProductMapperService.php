<?php

namespace App\Services\Integration\Stricker;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use App\Services\Integration\CategoryAssignmentService;
use Hitexis\Product\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StrickerProductMapperService extends BaseService
{
    protected ProductImportRepository $productImportRepository;

    protected CategoryAssignmentService $categoryAssignmentService;

    private bool $importPrices;

    public function __construct(ProductImportRepository $productImportRepository, CategoryAssignmentService $categoryAssignmentService)
    {
        $this->productImportRepository = $productImportRepository;
        $this->categoryAssignmentService = $categoryAssignmentService;
        $this->importPrices = env('IMPORT_PRICES', false);
    }

    public function mapProducts(): void
    {
        $attributeFamilyId = $this->productImportRepository->getDefaultAttributeFamily()->id;
        $parentProducts = collect($this->data['Products'])->map(function (array $row) use ($attributeFamilyId) {
            return [
                'sku'                 => $row['ProdReference'],
                'type'                => 'configurable',
                'attribute_family_id' => $attributeFamilyId,
            ];
        });

        $this->productImportRepository->upsertProducts($parentProducts);
    }

    public function mapProductSupplierCodes(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromProductJson());
        $midOceanIdentifier = env('STRICKER_IDENTIFIER', 'stricker');

        $supplierCodes = collect($products)->map(function (Product $row) use ($midOceanIdentifier) {
            return [
                'product_id'    => $row->id,
                'supplier_code' => $midOceanIdentifier,
            ];
        });

        $this->productImportRepository->upsertSupplierCodes($supplierCodes);
    }

    public function mapProductFlats(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromProductJson());
        $defaultChannelCode = $this->productImportRepository->getDefaultChannel()->code;
        $defaultAttributeFamilyId = $this->productImportRepository->getDefaultAttributeFamily()->id;

        $parentFlats = collect($this->data['Products'])->flatMap(function (array $row) use ($products, $defaultChannelCode, $defaultAttributeFamilyId) {
            $flatProducts = [];

            $flatProducts[] = [
                'sku'                       => $row['ProdReference'],
                'product_number'            => $row['ProdReference'],
                'type'                      => 'configurable',
                'name'                      => $row['Name'],
                'short_description'         => '<p>'.($row['ShortDescription'] ?? null).'</p>',
                'description'               => '<p>'.($row['Description'] ?? null).'</p>',
                'weight'                    => $row['BoxWeightKG'],
                'meta_keywords'             => $row['KeyWords'],
                'url_key'                   => Str::slug($row['ProdReference']),
                'product_id'                => $products[$row['ProdReference']]->id,
                'locale'                    => 'en',
                'new'                       => '1',
                'featured'                  => '1',
                'status'                    => '1',
                'channel'                   => $defaultChannelCode,
                'attribute_family_id'       => $defaultAttributeFamilyId,
                'visible_individually'      => 1,
            ];

            return $flatProducts;
        });

        $this->productImportRepository->upsertProductFlats($parentFlats);
    }

    public function mapProductCategories(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromProductJson());
        $parentCategories = collect($this->data['Products'])->flatMap(function (array $row) use ($products) {
            $categories = [];

            $typeCategoryId = $this->categoryAssignmentService->StrickerMapTypeToDefaultCategory($row['Type']);
            $subTypeCategoryId = isset($row['SubType'])
                ? $this->categoryAssignmentService->StrickerMapSubTypeToDefaultCategory($row['SubType'])
                : null;

            $isTypeUncategorized = $typeCategoryId == $this->categoryAssignmentService->getUncategorizedCategoryId();
            $isSubTypeUncategorized = $subTypeCategoryId === null || $subTypeCategoryId == $this->categoryAssignmentService->getUncategorizedCategoryId();

            if ($isTypeUncategorized && $isSubTypeUncategorized) {
                $categories[] = [
                    'product_id' => $products[$row['ProdReference']]->id,
                    'category_id'=> $typeCategoryId,
                ];
            } else {
                if (! $isTypeUncategorized) {
                    $categories[] = [
                        'product_id' => $products[$row['ProdReference']]->id,
                        'category_id'=> $typeCategoryId,
                    ];
                }
                if ($subTypeCategoryId !== null && ! $isSubTypeUncategorized) {
                    $categories[] = [
                        'product_id' => $products[$row['ProdReference']]->id,
                        'category_id'=> $subTypeCategoryId,
                    ];
                }
            }

            return $categories;
        })->filter();

        $this->productImportRepository->upsertProductCategories($parentCategories);
    }

    public function mapOptionalsCategories(): void
    {
        $optionals = $this->productImportRepository->getProducts($this->getOptionalsSKUCodesFromOptionalsJson());
        $parentCategories = collect($this->data['OptionalsComplete'])->flatMap(function (array $row) use ($optionals) {
            $categories = [];

            $typeCategoryId = $this->categoryAssignmentService->StrickerMapTypeToDefaultCategory($row['Type']);
            $subTypeCategoryId = isset($row['SubType'])
                ? $this->categoryAssignmentService->StrickerMapSubTypeToDefaultCategory($row['SubType'])
                : null;

            $isTypeUncategorized = $typeCategoryId == $this->categoryAssignmentService->getUncategorizedCategoryId();
            $isSubTypeUncategorized = $subTypeCategoryId === null || $subTypeCategoryId == $this->categoryAssignmentService->getUncategorizedCategoryId();

            if ($isTypeUncategorized && $isSubTypeUncategorized) {
                $categories[] = [
                    'product_id' => $optionals[$row['Sku']]->id,
                    'category_id'=> $typeCategoryId,
                ];
            } else {
                if (! $isTypeUncategorized) {
                    $categories[] = [
                        'product_id' => $optionals[$row['Sku']]->id,
                        'category_id'=> $typeCategoryId,
                    ];
                }

                if ($subTypeCategoryId !== null && ! $isSubTypeUncategorized) {
                    $categories[] = [
                        'product_id' => $optionals[$row['Sku']]->id,
                        'category_id'=> $subTypeCategoryId,
                    ];
                }
            }

            return $categories;
        })->filter();

        $this->productImportRepository->upsertProductCategories($parentCategories);
    }

    protected const PROD_ATR_MAP = [
        ['id' => 1,  'code' => 'ProdReference'],        // sku
        ['id' => 2,  'code' => 'Name'],                 // name
        ['id' => 3,  'code' => 'ProdReference'],        // url_key
        ['id' => 9,  'code' => 'ShortDescription'],     // short_description
        ['id' => 10, 'code' => 'Description'],          // description
        ['id' => 19, 'code' => 'BoxLengthMM'],          // length
        ['id' => 20, 'code' => 'BoxWidthMM'],           // width
        ['id' => 21, 'code' => 'BoxHeightMM'],          // height
        ['id' => 22, 'code' => 'BoxWeightKG'],          // weight (net_weight)
        ['id' => 25, 'code' => 'Brand'],                // brand
        ['id' => 29, 'code' => 'Materials'],            // material
        ['id' => 30, 'code' => 'CombinedSizes'],        // dimensions
        ['id' => 16, 'code' => 'KeyWords'],             // meta_keywords
        ['id' => 17, 'code' => 'ShortDescription'],     // meta_description
        ['id' => 27, 'code' => 'ProdReference'],        // product_number
    ];

    public function mapAttributeOptions(): void
    {
        $attributeOptions = collect($this->data['Products'])->flatMap(function ($item) {
            $attributeOptions = [];
            foreach (self::PROD_ATR_MAP as $attribute) {
                if (! empty($item[$attribute['code']])) {
                    $value = $item[$attribute['code']];

                    $attributeOptions[] = [
                        'attribute_id'  => $attribute['id'],
                        'admin_name'    => $value,
                    ];
                }
            }

            return $attributeOptions;
        })->filter();

        $this->productImportRepository->upsertAttributeOptions($attributeOptions);
    }

    protected const OPT_ATR_MAP = [
        ['id' => 23, 'code' => 'ColorDesc1'],           // color
        ['id' => 25, 'code' => 'Brand'],                // brand
        ['id' => 24, 'code' => 'Size'],                 // Size
        ['id' => 29, 'code' => 'Materials'],            // material
        ['id' => 30, 'code' => 'CombinedSizes'],        // dimensions
        ['id' => 22, 'code' => 'BoxWeightKG'],          // weight (net_weight)
        ['id' => 3,  'code' => 'Sku'],                  // url_key
        ['id' => 1,  'code' => 'Sku'],                  // url_key
        ['id' => 2,  'code' => 'Name'],                 // name
        ['id' => 27, 'code' => 'Sku'],                  // product_number
        ['id' => 11, 'code' => 'YourPrice'],            // price
        ['id' => 12, 'code' => 'YourPrice'],            // cost
    ];

    public function mapOptionalsAttributeOptions(): void
    {
        $attributeOptions = collect($this->data['OptionalsComplete'])->flatMap(function ($item) {
            $attributeOptions = [];
            foreach (self::OPT_ATR_MAP as $attribute) {
                if (! empty($item[$attribute['code']])) {
                    $value = $item[$attribute['code']];

                    $attributeOptions[] = [
                        'attribute_id'  => $attribute['id'],
                        'admin_name'    => $value,
                    ];
                }
            }

            return $attributeOptions;
        })->filter();

        $this->productImportRepository->upsertAttributeOptions($attributeOptions);
    }

    public function mapOptionals(): void
    {
        $products = $this->productImportRepository->getProducts($this->getParentSKUCodesFromOptionalsJson());
        $attributeFamilyId = $this->productImportRepository->getDefaultAttributeFamily()->id;

        $optionals = collect($this->data['OptionalsComplete'])->map(function (array $row) use ($products, $attributeFamilyId) {
            return [
                'sku'                 => $row['Sku'],
                'type'                => 'simple',
                'parent_id'           => $products[$row['ProdReference']]->id,
                'attribute_family_id' => $attributeFamilyId,
            ];
        });

        $this->productImportRepository->upsertProducts($optionals);
    }

    public function mapOptionalsSupplierCodes(): void
    {
        $products = $this->productImportRepository->getProducts($this->getOptionalsSKUCodesFromOptionalsJson());
        $midOceanIdentifier = env('STRICKER_IDENTIFIER', 'stricker');

        $supplierCodes = collect($products)->map(function (Product $row) use ($midOceanIdentifier) {
            return [
                'product_id'    => $row->id,
                'supplier_code' => $midOceanIdentifier,
            ];
        });

        $this->productImportRepository->upsertSupplierCodes($supplierCodes);
    }

    public function mapOptionalFlats(): void
    {
        $products = $this->productImportRepository->getProducts($this->getOptionalsSKUCodesFromOptionalsJson());
        $defaultChannelCode = $this->productImportRepository->getDefaultChannel()->code;
        $defaultAttributeFamilyId = $this->productImportRepository->getDefaultAttributeFamily()->id;

        $optionalFlats = collect($this->data['OptionalsComplete'])->map(function (array $row) use ($products, $defaultChannelCode, $defaultAttributeFamilyId) {
            $optionalArray = [
                'sku'                        => $row['Sku'],
                'product_number'             => $row['Sku'],
                'type'                       => 'simple',
                'name'                       => $row['Name'],
                'short_description'          => '<p>'.($row['ShortDescription'] ?? null).'</p>',
                'description'                => '<p>'.($row['Description'] ?? null).'</p>',
                'weight'                     => $row['BoxWeightKG'],
                'meta_keywords'              => $row['KeyWords'],
                'url_key'                    => Str::slug($row['Sku']),
                'product_id'                 => $products[$row['Sku']]->id,
                'locale'                     => 'en',
                'new'                        => '1',
                'featured'                   => '1',
                'status'                     => '1',
                'channel'                    => $defaultChannelCode,
                'attribute_family_id'        => $defaultAttributeFamilyId,
                'visible_individually'       => 0,
            ];

            if ($this->importPrices) {
                $optionalArray['price'] = $row['YourPrice'];
            }

            return $optionalArray;
        });

        $this->productImportRepository->upsertProductFlats($optionalFlats);
    }

    private function getSKUCodesFromProductJson(): Collection
    {
        return collect($this->data['Products'])->map(function ($item) {
            return [
                'sku'=> $item['ProdReference'],
            ];
        });
    }

    private function getParentSKUCodesFromOptionalsJson(): Collection
    {
        return collect($this->data['OptionalsComplete'])->map(function ($item) {
            return [
                'sku'=> $item['ProdReference'],
            ];
        });
    }

    private function getOptionalsSKUCodesFromOptionalsJson(): Collection
    {
        return collect($this->data['OptionalsComplete'])->map(function ($item) {
            return [
                'sku'=> $item['Sku'],
            ];
        });
    }

    private function mapAttributeValues(array $data, array $attributeMap, Collection $products, Collection $attributeOptions, callable $statusMapper, callable $guestCheckoutMapper, $referenceKey): Collection
    {
        return collect($data)->flatMap(function ($item) use ($products, $attributeOptions, $attributeMap, $statusMapper, $guestCheckoutMapper, $referenceKey) {
            $productAttributes = [];

            foreach ($attributeMap as $attribute) {
                if (! empty($item[$attribute['code']])) {
                    $text_value = $item[$attribute['code']];
                    $integer_value = null;
                    $float_value = null;

                    if (in_array($attribute['id'], [23, 24, 25]) && isset($attributeOptions[$item[$attribute['code']]])) {
                        $integer_value = $attributeOptions[$item[$attribute['code']]]->id;
                        $text_value = null;
                    }

                    if (in_array($attribute['id'], [11, 12])) {
                        $float_value = (float) $item[$attribute['code']];
                        $text_value = null;
                    }
                    //Only add price if the import prices is enabled
                    if ($attribute['id'] !== 11 || $this->importPrices) {
                        $productAttributes[] = [
                            'attribute_id'  => $attribute['id'],
                            'product_id'    => $products[$item[$referenceKey]]->id,
                            'text_value'    => $text_value,
                            'integer_value' => $integer_value,
                            'boolean_value' => null,
                            'float_value'   => $float_value,
                            'channel'       => 'default',
                            'locale'        => 'en',
                            'unique_id'     => 'default|en|'.$products[$item[$referenceKey]]->id.'|'.$attribute['id'],
                        ];
                    }
                }
            }
            $statusMapper($productAttributes, $products, $item);
            $guestCheckoutMapper($productAttributes, $products, $item);

            return $productAttributes;
        })->filter();
    }

    public function mapOptionalsAttributeValues(): void
    {
        $products = $this->productImportRepository->getProducts($this->getOptionalsSKUCodesFromOptionalsJson());
        $attributeOptions = $this->productImportRepository->getAttributeOptionsByName();

        $productAttributes = $this->mapAttributeValues(
            $this->data['OptionalsComplete'],
            self::OPT_ATR_MAP,
            $products,
            $attributeOptions,
            function (&$productAttributes, $products, $item) {
                $this->mapStatuses($productAttributes, $products, $item, 'Sku');
            },
            function (&$productAttributes, $products, $item) {
                $this->mapGuestCheckout($productAttributes, $products, $item, 'Sku');
            },
            'Sku'
        );

        $this->productImportRepository->upsertProductAttributeValues($productAttributes);
    }

    public function mapProductAttributeValues(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromProductJson());
        $attributeOptions = $this->productImportRepository->getAttributeOptionsByName();

        $productAttributes = $this->mapAttributeValues(
            $this->data['Products'],
            self::PROD_ATR_MAP,
            $products,
            $attributeOptions,
            function (&$productAttributes, $products, $item) {
                $this->mapStatuses($productAttributes, $products, $item, 'ProdReference');
            },
            function (&$productAttributes, $products, $item) {
                $this->mapGuestCheckout($productAttributes, $products, $item, 'ProdReference');
            },
            'ProdReference'
        );

        $this->productImportRepository->upsertProductAttributeValues($productAttributes);
    }

    protected const PRODUCT_STATUS_ATTRIBUTE_KEY = 8;

    private function mapStatuses(array &$productAttributes, Collection $products, array $item, string $referenceKey): void
    {
        $productAttributes[] = [
            'attribute_id'  => self::PRODUCT_STATUS_ATTRIBUTE_KEY,
            'product_id'    => $products[$item[$referenceKey]]->id,
            'text_value'    => null,
            'integer_value' => null,
            'float_value'   => null,
            'boolean_value' => true,
            'channel'       => 'default',
            'locale'        => 'en',
            'unique_id'     => 'default|en|'.$products[$item[$referenceKey]]->id.'|'.self::PRODUCT_STATUS_ATTRIBUTE_KEY,
        ];
    }

    protected const GUEST_CHECKOUT_ATTRIBUTE_KEY = 26;

    private function mapGuestCheckout(array &$productAttributes, Collection $products, array $item, string $referenceKey): void
    {
        $productAttributes[] = [
            'attribute_id'  => self::GUEST_CHECKOUT_ATTRIBUTE_KEY,
            'product_id'    => $products[$item[$referenceKey]]->id,
            'text_value'    => null,
            'integer_value' => null,
            'float_value'   => null,
            'boolean_value' => true,
            'channel'       => 'default',
            'locale'        => 'en',
            'unique_id'     => 'default|en|'.$products[$item[$referenceKey]]->id.'|'.self::GUEST_CHECKOUT_ATTRIBUTE_KEY,
        ];
    }

    public function mapProductImageURLs(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromProductJson());
        $productImageURLs = collect($this->data['Products'])->flatMap(function ($row) use ($products) {
            $productImageURLs = [];

            $imageUrls = explode(',', $row['AllImageList']);
            $imageUrls = array_combine(range(1, count($imageUrls)), $imageUrls);

            $productId = $products[$row['ProdReference']]->id;

            foreach ($imageUrls as $position => $imageUrl) {
                $productImageURLs[] = [
                    'url'       => config('integrations.stricker.hidea_images_url').trim($imageUrl),
                    'product_id'=> $productId,
                    'position'  => $position,
                    'type'      => 'image',
                ];
            }

            return $productImageURLs;
        });

        $this->productImportRepository->upsertProductURLImages($productImageURLs);
    }

    public function mapOptionalsImageURLs(): void
    {
        $products = $this->productImportRepository->getProducts($this->getOptionalsSKUCodesFromOptionalsJson());
        $productImageURLs = collect($this->data['OptionalsComplete'])->flatMap(function ($row) use ($products) {
            $productImageURLs = [];
            $imageUrls = explode(',', $row['AllImageList']);
            $imageUrls = array_combine(range(1, count($imageUrls)), $imageUrls);

            $productId = $products[$row['Sku']]->id;

            foreach ($imageUrls as $position => $imageUrl) {
                $productImageURLs[] = [
                    'url'       => config('integrations.stricker.hidea_images_url').trim($imageUrl),
                    'product_id'=> $productId,
                    'position'  => $position,
                    'type'      => 'image',
                ];
            }

            return $productImageURLs;
        });
        $this->productImportRepository->upsertProductURLImages($productImageURLs);
    }
}
