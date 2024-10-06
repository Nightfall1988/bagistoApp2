<?php

namespace App\Services\Integration\XDConnect;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use Illuminate\Support\Collection;

class XDConnectStockMapperService extends BaseService
{
    protected ProductImportRepository $productImportRepository;

    public function __construct(ProductImportRepository $productImportRepository)
    {
        $this->productImportRepository = $productImportRepository;
    }

    public function mapProductInventories(): void
    {
        $skuCollection = $this->getSKUsFromJSON($this->data);
        $products = $this->productImportRepository->getProducts($skuCollection);
        $channelID = $this->productImportRepository->getDefaultChannel()->id;

        $productInventories = collect($this->data)->map(function (array $row) use ($products, $channelID) {
            return [
                'qty'                => $row['CurrentStock'],
                'product_id'         => $products[$row['ItemCode']]->id,
                'vendor_id'          => 0,
                'inventory_source_id'=> $channelID,
            ];
        });

        $this->productImportRepository->upsertProductInventories($productInventories);
    }

    public function mapProductInventoryIndices(): void
    {
        $skuCollection = $this->getSKUsFromJSON($this->data);
        $products = $this->productImportRepository->getProducts($skuCollection);
        $channelID = $this->productImportRepository->getDefaultChannel()->id;

        $productInventoryIndices = collect($this->data)->map(function (array $row) use ($products, $channelID) {
            return [
                'qty'                => $row['CurrentStock'],
                'product_id'         => $products[$row['ItemCode']]->id,
                'channel_id'         => $channelID,
            ];
        });

        $this->productImportRepository->upsertProductInventoryIndices($productInventoryIndices);
    }

    private function getSKUsFromJSON(array $data): Collection
    {
        return collect($data)->map(function ($item) {
            return ['sku' => $item['ItemCode']];
        });
    }
}
