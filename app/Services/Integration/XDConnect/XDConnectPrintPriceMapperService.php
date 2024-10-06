<?php

namespace App\Services\Integration\XDConnect;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;
use Illuminate\Support\Str;

class XDConnectPrintPriceMapperService extends BaseService
{
    protected ProductImportRepository $productImportRepository;

    public function __construct(ProductImportRepository $productImportRepository)
    {
        $this->productImportRepository = $productImportRepository;
    }

    public function mapPrintTechniques(): void
    {
        $printTechniques = collect($this->data)->map(function (array $row) {
            return [
                'technique_id'              => Str::slug($row['PrintCode']),
                'description'               => $row['PrintTechnique'],
                'setup'                     => $row['SetupNet'],
            ];
        })->unique('technique_id')->values();

        $this->productImportRepository->upsertPrintTechniques($printTechniques);
    }

    public function mapPrintTechniqueVariableCosts(): void
    {

        $printTechniqueVariablePricing = collect($this->data)->map(function ($row) {
            $grossPrices = [
                $row['PrintPriceGross_1'], $row['PrintPriceGross_50'], $row['PrintPriceGross_100'],
                $row['PrintPriceGross_250'], $row['PrintPriceGross_500'], $row['PrintPriceGross_1000'],
                $row['PrintPriceGross_2500'], $row['PrintPriceGross_5000'], $row['PrintPriceGross_10000'],
            ];

            $grossPricesString = implode(',', $grossPrices);

            //Has to have a unique rangeID, since the collection of prices are unique in this dataset then they are used to create a hash for rangeID
            $rangeId = hash('crc32', $grossPricesString);

            return [
                'print_technique_id'    => Str::slug($row['PrintCode']),
                'range_id'              => $rangeId,
                'area_from'             => ! empty($row['PrintAreaFromCM2']) ? $row['PrintAreaFromCM2'] : null,
                'area_to'               => ! empty($row['PrintAreaToCM2']) ? $row['PrintAreaToCM2'] : null,
                'pricing_data'          => $this->generatePricingData($grossPrices),
            ];
        });

        $this->productImportRepository->upsertPrintVariableCosts($printTechniqueVariablePricing);
    }

    private function generatePricingData($inputData): string
    {
        $result = [];

        foreach ($inputData as $item) {
            $result[] = [
                'MinQt' => 0,
                'Price' => number_format($item, 2),
            ];
        }

        return json_encode($result);
    }
}
