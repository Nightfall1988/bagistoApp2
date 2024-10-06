<?php

namespace App\Services\Integration\MidOcean;

use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\BaseService;

class MidOceanPrintPriceMapperService extends BaseService
{
    protected ProductImportRepository $productImportRepository;
    public function __construct(ProductImportRepository $productImportRepository)
    {
        $this->productImportRepository = $productImportRepository;
    }
    public function mapPrintManipulations(): void
    {
        $printManipulations = collect($this->data['print_manipulations'])->map(function (array $row) {
            return [
                'currency'             => $this->data['currency'],
                'pricelist_valid_from' => $this->data['pricelist_valid_from'],
                'pricelist_valid_until'=> $this->data['pricelist_valid_until'],
                'code'                 => $row['code'],
                'description'          => $row['description'],
                'price'                => $this->valueToFloat($row['price']),
            ];
        });

        $this->productImportRepository->upsertPrintManipulations($printManipulations);
    }

    public function mapPrintTechniques(): void
    {
        $printTechniques = collect($this->data['print_techniques'])->map(function (array $row) {
            return [
                'technique_id'              => $row['id'],
                'description'               => $row['description'],
                'pricing_type'              => $row['pricing_type'],
                'setup'                     => $row['setup'],
                'setup_repeat'              => $row['setup_repeat'],
                'next_colour_cost_indicator'=> $row['next_colour_cost_indicator'] == 'true' ? 1 : 0,
            ];
        });
        $this->productImportRepository->upsertPrintTechniques($printTechniques);
    }

    public function mapPrintTechniqueVariableCosts(): void
    {
        $printTechniqueVariablePricing = collect($this->data['print_techniques'])->flatMap(function ($row) {
            return collect($row['var_costs'])->map(function ($costsRow) use ($row) {
                return [
                    'print_technique_id' => $row['id'],
                    'range_id'           => $costsRow['range_id'],
                    'area_from'          => $costsRow['area_from'],
                    'area_to'            => $costsRow['area_to'],
                    'pricing_data'       => isset($costsRow['scales']) ? $this->generatePricingData($costsRow['scales']) : null,
                ];
            })->all();
        });
        $this->productImportRepository->upsertPrintVariableCosts($printTechniqueVariablePricing);
    }

    //Todo: ielikt helperī
    private function valueToFloat(string $value): float
    {
        return (float) str_replace(',', '.', $value);
    }

    private function generatePricingData($inputData): string
    {
        $result = [];

        foreach ($inputData as $item) {
            $price = $this->valueToFloat($item['price']);

            $result[] = [
                'MinQt' => $item['minimum_quantity'],
                'Price' => number_format($price, 2),
            ];
        }

        return json_encode($result);
    }
}
