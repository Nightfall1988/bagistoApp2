<?php

namespace App\Services\Integration\MidOcean;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use Illuminate\Support\Collection;

class MidOceanStockMapperService extends BaseService
{
    protected ProductImportRepository $productImportRepository;

    public function __construct(ProductImportRepository $productImportRepository)
    {
        $this->productImportRepository = $productImportRepository;
    }

    public function mapProductInventories(): void
    {
        $skuCollection = $this->getSKUsFromJSON();
        $products = $this->productImportRepository->getProducts($skuCollection);
        $channelID = $this->productImportRepository->getDefaultChannel()->id;

        $productInventories = collect($this->data['stock'])->map(function (array $row) use ($products, $channelID) {
            if (isset($products[$row['sku']])) {
                return [
                    'qty'                => $row['qty'],
                    'product_id'         => $products[$row['sku']]->id,
                    'vendor_id'          => 0,
                    'inventory_source_id'=> $channelID,
                ];
            }

            return null;
        })->filter();

        $this->productImportRepository->upsertProductInventories($productInventories);
    }

    public function mapProductInventoryIndices(): void
    {
        $skuCollection = $this->getSKUsFromJSON();
        $products = $this->productImportRepository->getProducts($skuCollection);
        $channelID = $this->productImportRepository->getDefaultChannel()->id;

        $productInventoryIndices = collect($this->data['stock'])->map(function (array $row) use ($products, $channelID) {
            if (isset($products[$row['sku']])) {
                return [
                    'qty'                => $row['qty'],
                    'product_id'         => $products[$row['sku']]->id,
                    'channel_id'         => $channelID,
                ];
            }

            return null;
        })->filter();

        $this->productImportRepository->upsertProductInventoryIndices($productInventoryIndices);
    }

    private function getSKUsFromJSON(): Collection
    {
        return collect($this->data['stock'])->map(function ($item) {
            return ['sku' => $item['sku']];
        });
    }
}
