<?php

namespace App\Services\Integration\MidOcean;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use Illuminate\Support\Collection;

class MidOceanPrintDataMapperService extends BaseService
{
    protected ProductImportRepository $productImportRepository;

    protected Collection $productFlats;

    public function __construct(ProductImportRepository $productImportRepository)
    {
        $this->productImportRepository = $productImportRepository;
        $this->productFlats = collect();
    }

    public function loadData(array $data): void
    {
        $this->data = $data;
        $productNumbers = $this->getProductNumbersFromJSON();
        $this->productFlats = $this->productImportRepository->getProductFlatsFromProductNumbers($productNumbers);
    }

    public function mapProductPrintData(): void
    {
        $printManipulations = $this->productImportRepository->getAllPrintManipulations();

        $productPrintData = collect($this->data['products'])->map(function (array $row) use ($printManipulations) {
            if (isset($this->productFlats[$row['master_id']])) {
                return [
                    'product_id'                        => $this->productFlats[$row['master_id']]->product_id,
                    'print_manipulation_id'             => isset($printManipulations[$row['print_manipulation']]) ? $printManipulations[$row['print_manipulation']]->id : null,
                    'print_template'                    => $row['print_template'],
                ];
            }

            return null;
        })->filter();

        $this->productImportRepository->upsertProductPrintData($productPrintData);
    }

    public function mapPrintingPositions(): void
    {
        $productPrintData = $this->productImportRepository->getProductPrintDataFromProductFlats($this->productFlats);

        $printingPositions = collect($this->data['products'])->flatMap(function (array $row) use ($productPrintData) {
            $printingPositions = [];
            foreach ($row['printing_positions'] as $printingPosition) {
                if (isset($this->productFlats[$row['master_id']]) && isset($productPrintData[$this->productFlats[$row['master_id']]->product_id])) {
                    $productPrintDataId = $productPrintData[$this->productFlats[$row['master_id']]->product_id]->id;
                    $printingPositions[] = [
                        'product_print_data_id' => $productPrintDataId,
                        'position_id'           => $printingPosition['position_id'],
                        'print_size_unit'       => $printingPosition['print_size_unit'],
                        'max_print_size_height' => $printingPosition['max_print_size_height'],
                        'max_print_size_width'  => $printingPosition['max_print_size_width'],
                        'rotation'              => $printingPosition['rotation'],
                        'print_position_type'   => $printingPosition['print_position_type'],
                    ];
                }
            }

            return $printingPositions;
        })->filter();

        $this->productImportRepository->upsertPrintingPositions($printingPositions);
    }

    public function mapPositionPrintTechniques(): void
    {
        $productPrintData = $this->productImportRepository->getProductPrintDataFromProductFlats($this->productFlats);

        $printingPositionsData = $this->productImportRepository->getPrintingPositionsFromPrintData($productPrintData);

        $positionPrintTechniques = collect($this->data['products'])->flatMap(function (array $row) use ($productPrintData, $printingPositionsData) {
            $positionTechniques = [];
            foreach ($row['printing_positions'] as $printingPosition) {
                if (isset($this->productFlats[$row['master_id']]) && isset($productPrintData[$this->productFlats[$row['master_id']]->product_id])) {
                    $productPrintDataId = $productPrintData[$this->productFlats[$row['master_id']]->product_id]->id;
                    foreach ($printingPosition['printing_techniques'] as $printingTechnique) {
                        $positionTechniques[] = [
                            'printing_position_id' => $printingPositionsData[$productPrintDataId][$printingPosition['position_id']]->id,
                            'print_technique_id'   => $printingTechnique['id'],
                            'default'              => $printingTechnique['default'],
                            'max_colours'          => $printingTechnique['max_colours'],
                        ];
                    }
                }
            }

            return $positionTechniques;
        })->filter();

        $this->productImportRepository->upsertPositionPrintTechniques($positionPrintTechniques);
    }

    private function getProductNumbersFromJSON(): Collection
    {
        return collect($this->data['products'])->map(function ($item) {
            return ['product_number' => $item['master_id']];
        });
    }
}
