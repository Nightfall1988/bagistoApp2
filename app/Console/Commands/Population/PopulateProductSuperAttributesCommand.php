<?php

namespace App\Console\Commands\Population;

use App\Repositories\Population\PopulationRepository;
use Illuminate\Support\Collection;

class PopulateProductSuperAttributesCommand extends AbstractPopulateCommand
{
    private PopulationRepository $repository;
    public function __construct(PopulationRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'populate:product-super-attributes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates configurable product super attributes based on attributes of their children';

    /**
     * Execute the console command.
     */
    protected function getData(): Collection
    {
        return $this->repository->getConfigurableProductsWithAttributeValues();
    }

    const ALLOWED_SUPER_ATTRIBUTES = [
        23, //Color
        24, //Size
    ];

    protected function transformData(Collection $data): Collection
    {
        return $data->flatMap(function ($product) {
            $childAttributes = $product->variants->map(function ($variant) {
                return $variant->attribute_values->pluck('attribute_id')->unique();
            });

            $commonAttributes = $childAttributes->reduce(function ($carry, $item) {
                return $carry ? $carry->intersect($item) : $item;
            });

            $filteredAttributes = $commonAttributes->filter(function ($attributeId) {
                return in_array($attributeId, self::ALLOWED_SUPER_ATTRIBUTES);
            });

            return $filteredAttributes->map(function ($attributeId) use ($product) {
                return [
                    'product_id'   => $product->id,
                    'attribute_id' => $attributeId,
                ];
            });
        })->filter();
    }


    protected function upsertData(Collection $data): void
    {
        $this->repository->upsertSuperAttributeValues($data);
    }
}
