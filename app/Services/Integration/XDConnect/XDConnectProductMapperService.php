<?php

namespace App\Services\Integration\XDConnect;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use App\Services\Integration\CategoryAssignmentService;
use Hitexis\Product\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class XDConnectProductMapperService extends BaseService
{
    protected ProductImportRepository $productImportRepository;

    protected CategoryAssignmentService $categoryAssignmentService;

    public function __construct(ProductImportRepository $productImportRepository, CategoryAssignmentService $categoryAssignmentService)
    {
        $this->productImportRepository = $productImportRepository;
        $this->categoryAssignmentService = $categoryAssignmentService;
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
                'sku'                 => $row['ModelCode'],
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

        $variantProducts = collect($this->data)->map(function (array $row) use ($parentProducts, $attributeFamilyId) {
            $parentId = $parentProducts[$row['ModelCode']]->id;

            return [
                'sku'                 => $row['ItemCode'],
                'type'                => 'simple',
                'parent_id'           => $parentId,
                'attribute_family_id' => $attributeFamilyId,
            ];
        });

        $this->productImportRepository->upsertVariants($variantProducts);
    }

    public function mapSupplierCodes(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromJson());
        $xdConnectIdentifier = env('XDCONNECT_IDENTIFIER', 'xdconnect');

        $supplierCodes = collect($products)->map(function (Product $row) use ($xdConnectIdentifier) {
            return [
                'product_id'    => $row->id,
                'supplier_code' => $xdConnectIdentifier,
            ];
        });

        $this->productImportRepository->upsertSupplierCodes($supplierCodes);
    }

    public function mapProductFlats(): void
    {
        //XDConnect configurable product is only meant to unify all the simple variants together, so the configurable parent product doesn't have product flat
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromJson());

        $defaultChannelCode = $this->productImportRepository->getDefaultChannel()->code;
        $defaultAttributeFamilyId = $this->productImportRepository->getDefaultAttributeFamily()->id;

        $productFlats = collect($this->data)->map(function (array $row) use ($products, $defaultChannelCode, $defaultAttributeFamilyId) {
            return [
                'sku'                       => $row['ItemCode'],
                'type'                      => 'simple',
                'name'                      => $row['ItemName'],
                'short_description'         => '<p>'.($row['LongDescription'] ?? null).'</p>',
                'description'               => '<p>'.($row['LongDescription'] ?? null).'</p>',
                'weight'                    => $row['ItemWeightNetGr'],
                'url_key'                   => Str::slug($row['ModelCode'].'-'.$row['ItemCode']),
                'meta_title'                => $row['ItemName'],
                'meta_description'          => $row['LongDescription'],
                'product_id'                => $products[$row['ItemCode']]->id,
                'locale'                    => 'en',
                'new'                       => '1',
                'featured'                  => '1',
                'status'                    => '1',
                'channel'                   => $defaultChannelCode,
                'attribute_family_id'       => $defaultAttributeFamilyId,
                'visible_individually'      => 1,

            ];
        });

        //A lot of data could pile up during this product flat upsert, could encounter packet size limit (SQLSTATE[08S01])
        //Adjust batchsize accordingly
        $this->productImportRepository->upsertProductFlats($productFlats, 100);
    }

    protected const COMMON_ATR_MAP = [
        ['id' => 1,  'code' => 'ItemCode'],  // SKU corresponds to ItemCode
        ['id' => 2,  'code' => 'ItemName'],  // Name corresponds to ItemName
        ['id' => 3,  'code' => 'ItemName'],  // URL Key could use ItemName as a base
        ['id' => 4,  'code' => 'CommodityCode'],  // Tax Category could map to CommodityCode
        ['id' => 8,  'code' => 'Eco'],  // Status could correspond to the "Eco" field
        ['id' => 9,  'code' => 'LongDescription'],  // Short Description maps to LongDescription
        ['id' => 10, 'code' => 'LongDescription'],  // Description also maps to LongDescription
        ['id' => 19, 'code' => 'ItemLengthCM'],  // Length corresponds to ItemLengthCM
        ['id' => 20, 'code' => 'ItemWidthCM'],  // Width corresponds to ItemWidthCM
        ['id' => 21, 'code' => 'ItemHeightCM'],  // Height corresponds to ItemHeightCM
        ['id' => 22, 'code' => 'ItemWeightNetGr'],  // Weight corresponds to ItemWeightNetGr
        ['id' => 23, 'code' => 'Color'],  // Color corresponds to Color
        ['id' => 23, 'code' => 'PMSColor1'],  // Color corresponds to Color
        ['id' => 23, 'code' => 'PMSColor2'],  // Color corresponds to Color
        ['id' => 24, 'code' => 'Size'],  // Size corresponds to a potential Size field
        ['id' => 25, 'code' => 'Brand'],  // Brand corresponds to Brand
        ['id' => 29, 'code' => 'Material'],  // Material corresponds to Material
        ['id' => 30, 'code' => 'ItemDimensions'],  // Dimensions corresponds to ItemDimensions
    ];

    public function mapProductAttributeValues(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromJson());
        $attributeOptions = $this->productImportRepository->getAttributeOptionsByName();

        $productAttributes = collect($this->data)->flatMap(function ($item) use ($products, $attributeOptions) {
            $productAttributes = [];
            foreach (self::COMMON_ATR_MAP as $attribute) {
                if (! empty($item[$attribute['code']])) {
                    $text_value = $item[$attribute['code']];
                    $integer_value = null;

                    if ($attribute['id'] == 3) {
                        $text_value = Str::slug($item['ModelCode'].'-'.$item['ItemCode']);
                    }
                    if ($attribute['id'] == 23 || $attribute['id'] == 24) {
                        if (isset($attributeOptions[$item[$attribute['code']]])) {
                            $integer_value = $attributeOptions[$item[$attribute['code']]]->id;
                        }
                    }

                    $productAttributes[] = [
                        'attribute_id'  => $attribute['id'],
                        'product_id'    => $products[$item['ItemCode']]->id,
                        'text_value'    => $text_value,
                        'integer_value' => $integer_value,
                        'boolean_value' => null,
                        'channel'       => 'default',
                        'locale'        => 'en',
                        'unique_id'     => 'default|en|'.$products[$item['ItemCode']]->id.'|'.$attribute['id'],
                    ];
                }
            }

            $this->mapProductVisibilities($productAttributes, $products, $item);

            return $productAttributes;
        })->filter();

        $this->productImportRepository->upsertProductAttributeValues($productAttributes);
    }

    protected const PRODUCT_VISIBILITY_ATTRIBUTE_KEY = 7;

    private function mapProductVisibilities(array &$productAttributes, Collection $products, array $item): void
    {
        $productAttributes[] = [
            'attribute_id'  => self::PRODUCT_VISIBILITY_ATTRIBUTE_KEY,
            'product_id'    => $products[$item['ItemCode']]->id,
            'text_value'    => null,
            'integer_value' => null,
            'boolean_value' => true,
            'channel'       => 'default',
            'locale'        => 'en',
            'unique_id'     => 'default|en|'.$products[$item['ItemCode']]->id.'|'.self::PRODUCT_VISIBILITY_ATTRIBUTE_KEY,
        ];
    }

    protected const ATR_OPT_MAP = [
        ['id' => 8,  'code' => 'Eco'],  // Status could correspond to the "Eco" field
        ['id' => 19, 'code' => 'ItemLengthCM'],  // Length corresponds to ItemLengthCM
        ['id' => 20, 'code' => 'ItemWidthCM'],  // Width corresponds to ItemWidthCM
        ['id' => 21, 'code' => 'ItemHeightCM'],  // Height corresponds to ItemHeightCM
        ['id' => 22, 'code' => 'ItemWeightNetGr'],  // Weight corresponds to ItemWeightNetGr
        ['id' => 23, 'code' => 'Color'],  // Color corresponds to Color
        ['id' => 23, 'code' => 'PMSColor1'],  // Color corresponds to Color
        ['id' => 23, 'code' => 'PMSColor2'],  // Color corresponds to Color
        ['id' => 24, 'code' => 'ItemDimensions'],  // Size corresponds to a potential Size field
        ['id' => 25, 'code' => 'Brand'],  // Brand corresponds to Brand
        ['id' => 29, 'code' => 'Material'],  // Material corresponds to Material
        ['id' => 30, 'code' => 'ItemDimensions'],  // Dimensions corresponds to ItemDimensions
    ];

    public function mapAttributeOptions(): void
    {
        $attributeOptions = collect($this->data)->flatMap(function ($item) {
            $attributeOptions = [];
            foreach (self::ATR_OPT_MAP as $attribute) {
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

    public function mapProductCategories(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromJson());
        $parentCategories = collect($this->data)->flatMap(function (array $row) use ($products) {
            $categories = [];

            $categories[] = [
                'product_id' => $products[$row['ItemCode']]->id,
                'category_id'=> $this->categoryAssignmentService->XDConnectMapTypeToDefaultCategory($row['MainCategory']),
            ];

            if (! empty($row['SubCategory'])) {
                $categories[] = [
                    'product_id' => $products[$row['ItemCode']]->id,
                    'category_id'=> $this->categoryAssignmentService->XDConnectMapSubTypeToDefaultCategory($row['SubCategory']),
                ];
            }

            return $categories;
        })->filter();

        $this->productImportRepository->upsertProductCategories($parentCategories);
    }

    public function mapProductImageURLs(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromJson());
        $productImageURLs = collect($this->data)->flatMap(function ($row) use ($products) {
            $productImageURLs = [];

            $imageUrls = explode(',', $row['AllImages']);
            $imageUrls = array_combine(range(1, count($imageUrls)), $imageUrls);

            $productId = $products[$row['ItemCode']]->id;

            foreach ($imageUrls as $position => $imageUrl) {
                $productImageURLs[] = [
                    'url'       => $imageUrl,
                    'product_id'=> $productId,
                    'position'  => $position,
                    'type'      => 'image',
                ];
            }

            return $productImageURLs;
        });

        $this->productImportRepository->upsertProductURLImages($productImageURLs);
    }

    private function getSKUCodesFromJson(): Collection
    {
        return collect($this->data)->map(function ($item) {
            return [
                'sku' => $item['ItemCode'],
            ];
        });
    }
}
