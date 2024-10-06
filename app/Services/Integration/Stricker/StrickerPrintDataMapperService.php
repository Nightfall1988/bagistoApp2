<?php

namespace App\Services\Integration\Stricker;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use App\Services\Integration\CategoryAssignmentService;
use Illuminate\Support\Collection;

class StrickerPrintDataMapperService extends BaseService
{
    protected ProductImportRepository $productImportRepository;

    protected CategoryAssignmentService $categoryAssignmentService;

    public function __construct(ProductImportRepository $productImportRepository, CategoryAssignmentService $categoryAssignmentService)
    {
        $this->productImportRepository = $productImportRepository;
        $this->categoryAssignmentService = $categoryAssignmentService;
    }

    public function mapProductPrintData(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUsFromJson());

        $productPrintData = collect($this->data['CustomizationOptions'])->map(function (array $row) use ($products) {
            return [
                'product_id'  => $products[$row['ProdReference']]->id,
            ];
        })->unique('product_id')->values();

        $this->productImportRepository->upsertProductPrintData($productPrintData);
    }

    public function mapPrintingPositions(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUsFromJson());
        $printData = $this->productImportRepository->getProductPrintDataFromProducts($products);

        $printingPositions = collect($this->data['CustomizationOptions'])->map(function (array $row) use ($printData, $products) {
            [$height, $width] = explode(' x ', $row['TableMaxAreaCM']);

            return [
                'product_print_data_id' => $printData[$products[$row['ProdReference']]->id]->id,
                'position_id'           => $row['Location'],
                'max_print_size_height' => $height,
                'max_print_size_width'  => $width,
                'print_position_type'   => $row['HotSpot1Type'],
            ];

        })->unique(function ($item) {
            return $item['product_print_data_id'].'_'.$item['position_id'];
        })->values();

        $this->productImportRepository->upsertPrintingPositions($printingPositions);
    }

    public function mapPrintTechniques(): Collection
    {
        $printTechniques = collect($this->data['CustomizationOptions'])->map(function (array $row) {
            return [
                'technique_id'              => $row['ProdReference'].'-'.$row['TableCode'],
                'description'               => $row['CustomizationTypeName'],
            ];
        })->unique('technique_id')->values();

        $this->productImportRepository->upsertPrintTechniques($printTechniques);

        return $printTechniques;
    }

    public function mapTechniqueVariableCosts(): void
    {
        //Each product has its own print price cost and minimumQT independent of printing technique
        //Essentially each row is a variable cost
        $techniqueVariableCosts = collect($this->data['CustomizationOptions'])->map(function (array $row) {
            return [
                'print_technique_id'  => $row['ProdReference'].'-'.$row['TableCode'],
                'range_id'            => str_replace($row['TableCode'].'-', '', $row['TableCodeOption']),
                'area_to'             => $row['TableMaxAreaCM2'],
                'pricing_data'        => $this->normalizeMinQtAndPrice($row),
            ];
        });

        $this->productImportRepository->upsertPrintVariableCosts($techniqueVariableCosts);
    }

    public function mapPositionPrintTechniques(): void
    {
        $products = $this->productImportRepository->getProducts($this->getSKUsFromJson());
        $productPrintData = $this->productImportRepository->getProductPrintDataFromProducts($products);
        $printingPositionsData = $this->productImportRepository->getPrintingPositionsFromPrintData($productPrintData);

        $positionPrintTechniques = collect($this->data['CustomizationOptions'])->map(function (array $row) use ($printingPositionsData, $productPrintData, $products) {
            $productID = $products[$row['ProdReference']]->id;
            $productPrintDataID = $productPrintData[$productID]->id;
            return [
                'printing_position_id' => $printingPositionsData[$productPrintDataID][$row['Location']]->id,
                'print_technique_id'   => $row['ProdReference'].'-'.$row['TableCode'],
                'default'              => $row['IsDefault'],
                'max_colours'          => $row['MaxColors'],
            ];
        });

        $this->productImportRepository->upsertPositionPrintTechniques($positionPrintTechniques);
    }

    private function getSKUsFromJson(): Collection
    {
        return collect($this->data['CustomizationOptions'])->map(function ($item) {
            return ['sku' => $item['ProdReference']];
        });
    }

    private function normalizeMinQtAndPrice($row): string|bool
    {
        $result = [];

        foreach ($row as $key => $value) {
            if (str_starts_with($key, 'MinQt') && ! is_null($value)) {
                $index = str_replace('MinQt', '', $key);

                $priceKey = 'Price'.$index;

                if (isset($row[$priceKey])) {
                    $result[] = [
                        'MinQt' => number_format($value, 0, '.', '.'),
                        'Price' => number_format($row[$priceKey], 3, '.', ''),
                    ];
                }
            }
        }

        return json_encode($result);
    }
}
