<?php

namespace App\Services\Integration\Stricker;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use App\Services\Integration\CategoryAssignmentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StrickerProductMapperService extends BaseService
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
        $parentProducts = collect($this->data['Products'])->map(function (array $row) {
            return [
                'sku'  => $row['ProdReference'],
                'type' => 'configurable',
            ];
        });

        $this->productImportRepository->upsertProducts($parentProducts);
    }

    public function mapProductFlats(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromProductJson());

        $parentFlats = collect($this->data['Products'])->flatMap(function (array $row) use ($products) {
            $flatProducts = [];

            $flatProducts[] = [
                'sku'                       => $row['ProdReference'],
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

            $categories[] = [
                'product_id' => $products[$row['ProdReference']]->id,
                'category_id'=> $this->categoryAssignmentService->StrickerMapTypeToDefaultCategory($row['Type']),
            ];

            if (isset($row['SubType'])) {
                $categories[] = [
                    'product_id' => $products[$row['ProdReference']]->id,
                    'category_id'=> $this->categoryAssignmentService->StrickerMapSubTypeToDefaultCategory($row['SubType']),
                ];
            }

            return $categories;
        });

        $this->productImportRepository->upsertProductCategories($parentCategories);
    }

    protected const COMMON_ATR_MAP = [
        ['id' => 1,  'code' => 'ProdReference'],         // sku
        ['id' => 2,  'code' => 'Name'],                 // name
        ['id' => 3,  'code' => 'SEOName'],              // url_key
        ['id' => 9,  'code' => 'ShortDescription'],     // short_description
        ['id' => 10, 'code' => 'Description'],          // description
        ['id' => 19, 'code' => 'BoxLengthMM'],          // length
        ['id' => 20, 'code' => 'BoxWidthMM'],           // width
        ['id' => 21, 'code' => 'BoxHeightMM'],          // height
        ['id' => 22, 'code' => 'BoxWeightKG'],          // weight (net_weight)
        ['id' => 23, 'code' => 'Colors'],               // color
        ['id' => 24, 'code' => 'CombinedSizes'],        // size
        ['id' => 25, 'code' => 'Brand'],                // brand
        ['id' => 29, 'code' => 'Materials'],            // material
        ['id' => 30, 'code' => 'CombinedSizes'],        // dimensions
        ['id' => 16, 'code' => 'KeyWords'],             // meta_keywords
        ['id' => 17, 'code' => 'ShortDescription'],     // meta_description
    ];

    public function mapProductAttributeValues(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUCodesFromProductJson());

        $productAttributes = collect($this->data['Products'])->flatMap(function ($item) use ($products) {
            $productAttributes = [];
            foreach (self::COMMON_ATR_MAP as $attribute) {
                if (! empty($item[$attribute['code']])) {
                    $productAttributes[] = [
                        'attribute_id'  => $attribute['id'],
                        'product_id'    => $products[$item['ProdReference']]->id,
                        'text_value'    => $item[$attribute['code']],
                        'channel'       => 'default',
                        'locale'        => 'en',
                        'unique_id'     => 'default|en|'.$products[$item['ProdReference']]->id.'|'.$attribute['id'],
                    ];
                }
            }

            return $productAttributes;
        })->filter();

        $this->productImportRepository->upsertProductAttributeValues($productAttributes);
    }

    public function mapOptionals(): void
    {
        $products = $this->productImportRepository->getProducts($this->getParentSKUCodesFromOptionalsJson());

        $optionals = collect($this->data['OptionalsComplete'])->map(function (array $row) use ($products) {
            return [
                'sku'       => $row['Sku'],
                'type'      => 'simple',
                'parent_id' => $products[$row['ProdReference']]->id,
            ];
        });

        $this->productImportRepository->upsertProducts($optionals);
    }

    public function mapOptionalFlats(): void
    {
        $products = $this->productImportRepository->getProducts($this->getOptionalsSKUCodesFromOptionalsJson());

        $optionalFlats = collect($this->data['OptionalsComplete'])->map(function (array $row) use ($products) {
            return [
                'sku'                        => $row['Sku'],
                'type'                       => 'simple',
                'name'                       => $row['Name'],
                'price'                      => $row['YourPrice'],
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

            ];
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
}
