<?php

namespace App\Console\Commands\Population;

use App\Repositories\Integration\ProductImportRepository;
use App\Repositories\Population\PopulationRepository;
use Illuminate\Support\Collection;

class PopulateConfigurableProductPricesCommand extends AbstractPopulateCommand
{
    private PopulationRepository $repository;

    private ProductImportRepository $productImportRepository;

    public function __construct(PopulationRepository $repository, ProductImportRepository $productImportRepository)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->productImportRepository = $productImportRepository;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'populate:configurable-product-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates configurable product prices';

    /**
     * Execute the console command.
     */
    protected function getData(): Collection
    {
        return $this->repository->getConfigurableProductsWithNoPrices();
    }

    private const PRICE_ATR = 11;

    protected function transformData(Collection $data): Collection
    {
        $customerGroups = $this->productImportRepository->getCustomerGroups();

        return collect($data)->map(function ($product) use ($customerGroups) {
            $productFlat = $product->product_flats->first();

            if ($productFlat) {
                $cheapestPrice = collect($product->variants)
                    ->pluck('product_flats')
                    ->flatten()
                    ->pluck('price')
                    ->filter()
                    ->min();

                if (! is_null($cheapestPrice)) {
                    $priceIndices = collect($customerGroups)->map(function ($customerGroup) use ($cheapestPrice, $productFlat) {
                        return [
                            'product_id'        => $productFlat->product_id,
                            'customer_group_id' => $customerGroup->id,
                            'channel_id'        => 1,
                            'min_price'         => (float) $cheapestPrice,
                            'regular_min_price' => (float) $cheapestPrice,
                            'max_price'         => (float) $cheapestPrice,
                            'regular_max_price' => (float) $cheapestPrice,
                        ];
                    });

                    // Return structured data
                    return [
                        'product_flat' => [
                            'sku'        => $productFlat->sku,
                            'price'      => $cheapestPrice,
                            'product_id' => $productFlat->product_id,
                            'locale'     => $productFlat->locale,
                            'channel'    => $productFlat->channel,
                        ],
                        'product_attribute_value' => [
                            'float_value'   => (float) $cheapestPrice,
                            'product_id'    => $productFlat->product_id,
                            'attribute_id'  => self::PRICE_ATR,
                            'channel'       => 'default',
                            'locale'        => 'en',
                            'unique_id'     => 'default|en|'.$productFlat->product_id.'|'.self::PRICE_ATR,
                        ],
                        'product_price_indices' => $priceIndices->all(),
                    ];
                }
            }

            return null;
        })->filter();
    }

    protected function upsertData(Collection $data): void
    {
        $this->productImportRepository->upsertProductFlatPrices($data->pluck('product_flat'));
        $this->productImportRepository->upsertProductAttributeValuePrices($data->pluck('product_attribute_value'));
        $this->productImportRepository->upsertProductPriceIndices($data->pluck('product_price_indices')->flatten(1));
    }
}
