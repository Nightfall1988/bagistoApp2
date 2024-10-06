<?php

namespace App\Services\Integration\XDConnect;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use Illuminate\Support\Collection;

class XDConnectProductPriceMapperService extends BaseService
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
        return collect($this->data)->map(function ($item) {
            return ['sku' => $item['ItemCode']];
        });
    }

    private const PRICE_ATR = 11;

    public function mapProductAttributeValuePrices(): void
    {
        $productAttributeValuePrices = collect($this->data)->map(function (array $row) {
            return [
                'float_value'   => (float) $row['ItemPriceGross_Qty1'],
                'product_id'    => $this->products[$row['ItemCode']]->id,
                'attribute_id'  => self::PRICE_ATR,
                'channel'       => 'default',
                'locale'        => 'en',
                'unique_id'     => 'default|en|'.$this->products[$row['ItemCode']]->id.'|'.self::PRICE_ATR,
            ];
        });

        $this->productImportRepository->upsertProductAttributeValuePrices($productAttributeValuePrices);
    }

    public function mapProductFlatPrices(): void
    {
        $productFlatPrices = collect($this->data)->map(function (array $row) {
            return [
                'sku'        => $row['ItemCode'],
                'price'      => (float) $row['ItemPriceGross_Qty1'],
                'product_id' => $this->products[$row['ItemCode']]->id,
            ];
        });

        $this->productImportRepository->upsertProductFlatPrices($productFlatPrices);
    }

    public function mapProductPriceIndices(): void
    {

        $customerGroups = $this->productImportRepository->getCustomerGroups();

        $productPriceIndices = collect($this->data)->flatMap(function ($item) use ($customerGroups) {
            $priceIndices = [];
            foreach ($customerGroups as $customerGroup) {
                $priceIndices[] = [
                    'product_id'        => $this->products[$item['ItemCode']]->id,
                    'customer_group_id' => $customerGroup->id,
                    'channel_id'        => 1,
                    'min_price'         => (float) $item['ItemPriceGross_Qty6'],
                    'regular_min_price' => (float) $item['ItemPriceGross_Qty6'],
                    'max_price'         => (float) $item['ItemPriceGross_Qty1'],
                    'regular_max_price' => (float) $item['ItemPriceGross_Qty1'],
                ];
            }

            return $priceIndices;
        })->filter();

        $this->productImportRepository->upsertProductPriceIndices($productPriceIndices);
    }
}
