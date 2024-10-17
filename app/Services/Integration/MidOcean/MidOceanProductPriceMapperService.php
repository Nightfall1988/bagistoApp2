<?php

namespace App\Services\Integration\MidOcean;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use Illuminate\Support\Collection;

class MidOceanProductPriceMapperService extends BaseService
{
    protected ProductImportRepository $productImportRepository;

    protected Collection $products;

    public function __construct(ProductImportRepository $productImportRepository)
    {
        $this->productImportRepository = $productImportRepository;
        $this->products = collect();
    }

    public function loadData(array $data): void
    {
        $this->data = $data;
        $skuCodes = $this->getSKUCodesFromJson();
        $this->products = $this->productImportRepository->getProducts($skuCodes);
    }

    public function getSKUCodesFromJson(): Collection
    {
        return collect($this->data['price'])->map(function ($item) {
            return ['sku' => $item['sku']];
        });
    }

    private const PRICE_ATR = 11;

    public function mapProductAttributeValuePrices(): void
    {
        $productAttributeValuePrices = collect($this->data['price'])->map(function (array $row) {
            if (isset($this->products[$row['sku']])) {
                return [
                    'float_value'   => $this->valueToFloat($row['price']),
                    'product_id'    => $this->products[$row['sku']]->id,
                    'attribute_id'  => self::PRICE_ATR,
                    'channel'       => 'default',
                    'locale'        => 'en',
                    'unique_id'     => 'default|en|'.$this->products[$row['sku']]->id.'|'.self::PRICE_ATR,
                ];
            }

            return null;
        })->filter();

        $this->productImportRepository->upsertProductAttributeValuePrices($productAttributeValuePrices);
    }

    public function mapProductFlatPrices(): void
    {
        $productFlatPrices = collect($this->data['price'])->map(function (array $row) {
            if (isset($this->products[$row['sku']])) {
                return [
                    'sku'        => $row['sku'],
                    'price'      => $this->valueToFloat($row['price']),
                    'product_id' => $this->products[$row['sku']]->id,
                    'locale'     => 'en',
                    'channel'    => 'default',
                ];
            }

            return null;
        })->filter();

        $this->productImportRepository->upsertProductFlatPrices($productFlatPrices);
    }

    public function mapProductPriceIndices(): void
    {
        $customerGroups = $this->productImportRepository->getCustomerGroups();

        $productPriceIndices = collect($this->data['price'])->flatMap(function ($item) use ($customerGroups) {
            $priceIndices = [];
            if (isset($this->products[$item['sku']])) {
                foreach ($customerGroups as $customerGroup) {
                    $itemPrice = $this->valueToFloat($item['price']);
                    $priceIndices[] = [
                        'product_id'        => $this->products[$item['sku']]->id,
                        'customer_group_id' => $customerGroup->id,
                        'channel_id'        => 1,
                        'min_price'         => $itemPrice,
                        'regular_min_price' => $itemPrice,
                        'max_price'         => $itemPrice,
                        'regular_max_price' => $itemPrice,
                    ];
                }
            }

            return $priceIndices;
        })->filter();

        $this->productImportRepository->upsertProductPriceIndices($productPriceIndices);
    }

    private function valueToFloat(string $value): float
    {
        return (float) str_replace(',', '.', $value);
    }
}
