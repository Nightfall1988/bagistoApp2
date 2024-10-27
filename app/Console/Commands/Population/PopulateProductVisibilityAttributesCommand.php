<?php

namespace App\Console\Commands\Population;

use App\Repositories\Population\PopulationRepository;
use Illuminate\Support\Collection;

class PopulateProductVisibilityAttributesCommand extends AbstractPopulateCommand
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
    protected $signature = 'populate:product-attribute_visibilites';

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
        return $this->repository->getProductsWithVariants();
    }

    protected const PRODUCT_VISIBILITY_ATTRIBUTE_KEY = 7;

    protected function transformData(Collection $data): Collection
    {
        $locales = $this->repository->getLocaleCodes();

        return $data->map(function ($configurableProduct) use ($locales) {
            $hasSingleVariant = count($configurableProduct->variants) === 1;
            $productAttributes = [];

            foreach ($locales as $locale) {
                $productAttributes[] = [
                    'attribute_id'  => self::PRODUCT_VISIBILITY_ATTRIBUTE_KEY,
                    'product_id'    => $configurableProduct->id,
                    'text_value'    => null,
                    'integer_value' => null,
                    'boolean_value' => $hasSingleVariant ? 0 : 1,
                    'channel'       => 'default',
                    'locale'        => $locale,
                    'unique_id'     => 'default|'.$locale.'|'.$configurableProduct->id.'|'.self::PRODUCT_VISIBILITY_ATTRIBUTE_KEY,
                ];
                foreach ($configurableProduct->variants as $variant) {
                    $productAttributes[] = [
                        'attribute_id'  => self::PRODUCT_VISIBILITY_ATTRIBUTE_KEY,
                        'product_id'    => $variant->id,
                        'text_value'    => null,
                        'integer_value' => null,
                        'boolean_value' => $hasSingleVariant ? 1 : 0,
                        'channel'       => 'default',
                        'locale'        => $locale,
                        'unique_id'     => 'default|'.$locale.'|'.$variant->id.'|'.self::PRODUCT_VISIBILITY_ATTRIBUTE_KEY,
                    ];
                }
            }

            return $productAttributes;
        });
    }

    protected function upsertData(Collection $data): void
    {
        $this->repository->upsertProductAttributeValues($data->flatten(1));
    }
}
