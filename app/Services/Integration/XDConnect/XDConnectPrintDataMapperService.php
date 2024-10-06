<?php

namespace App\Services\Integration\XDConnect;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use Hitexis\Product\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class XDConnectPrintDataMapperService extends BaseService
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
        $productNumbers = $this->getSKUsFromJSON($data);
        $this->products = $this->productImportRepository->getProducts($productNumbers);
    }

    public function mapProductPrintData(): void
    {

        $productPrintData = collect($this->products)->map(function (Product $item) {
            return [
                'product_id' => $item->id,
            ];
        });

        $this->productImportRepository->upsertProductPrintData($productPrintData);
    }
    public function mapPrintingPositions(): void
    {
        $productPrintData = $this->productImportRepository->getProductPrintDataFromProducts($this->products);

        $printingPositions = collect($this->data)->map(function (array $row) use ($productPrintData) {
            $productPrintDataId = $productPrintData[$this->products[$row['ItemCode']]->id]->id;
            return [
                'product_print_data_id' => $productPrintDataId,
                'position_id'           => $row['PrintPositionCode'],
                'print_size_unit'       => 'mm',
                'max_print_size_height' => $row['MaxPrintHeightMM'],
                'max_print_size_width'  => $row['MaxPrintWidthMM'],
            ];
        });

        $this->productImportRepository->upsertPrintingPositions($printingPositions);
    }

    public function mapPositionPrintTechniques(): void
    {
        $productPrintData = $this->productImportRepository->getProductPrintDataFromProducts($this->products);
        $printingPositionsData = $this->productImportRepository->getPrintingPositionsFromPrintData($productPrintData);

        $positionPrintTechniques = collect($this->data)->map(function (array $row) use ($productPrintData, $printingPositionsData) {
            $productPrintDataId = $productPrintData[$this->products[$row['ItemCode']]->id]->id;

            return [
                'printing_position_id' => $printingPositionsData[$productPrintDataId][$row['PrintPositionCode']]->id,
                'print_technique_id'   => Str::slug($row['PrintCode']),
                'default'              => $row['Default'],
                'max_colours'          => $row['MaxColors'],
            ];
        });

        $this->productImportRepository->upsertPositionPrintTechniques($positionPrintTechniques);
    }

    //TODO: visus šos helperī salikt
    private function getSKUsFromJSON(array $data): Collection
    {
        return collect($data)->map(function ($item) {
            return ['sku' => $item['ItemCode']];
        })->unique('sku');
    }
}
