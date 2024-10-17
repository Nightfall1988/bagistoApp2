<?php

namespace App\Console\Commands\Population;

use App\Repositories\Population\PopulationRepository;
use Illuminate\Support\Collection;

class DuplicateProductAttributeValuesCommand extends AbstractPopulateCommand
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
    protected $signature = 'duplicate:product-attribute_values-lv';

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
        return $this->repository->getEnLocaleProductAttributesWithNoLvCounterParts();
    }

    protected const URL_KEY_ATTRIBUTE_ID = 3;

    protected function transformData(Collection $data): Collection
    {
        return $data->map(function ($productAttributeValue) {
            return [
                'locale'            => 'lv',
                'channel'           => $productAttributeValue->channel,
                'text_value'        => $productAttributeValue->attribute_id == self::URL_KEY_ATTRIBUTE_ID
                    ? $productAttributeValue->text_value.'-lv'
                    : $productAttributeValue->text_value,
                'boolean_value'     => $productAttributeValue->boolean_value,
                'integer_value'     => $productAttributeValue->integer_value,
                'float_value'       => $productAttributeValue->float_value,
                'datetime_value'    => $productAttributeValue->datetime_value,
                'date_value'        => $productAttributeValue->date_value,
                'json_value'        => $productAttributeValue->json_value,
                'product_id'        => $productAttributeValue->product_id,
                'attribute_id'      => $productAttributeValue->attribute_id,
                'unique_id'         => str_replace('|en|', '|lv|', $productAttributeValue->unique_id),
            ];
        });
    }

    protected function upsertData(Collection $data): void
    {
        $this->repository->upsertProductAttributeValueDuplicates($data);
    }
}
