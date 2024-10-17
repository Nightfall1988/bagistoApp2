<?php

namespace App\Console\Commands\Population;

use App\Repositories\Population\PopulationRepository;
use Illuminate\Support\Collection;

class DuplicateProductFlatLvLocaleCommand extends AbstractPopulateCommand
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
    protected $signature = 'duplicate:product-flat-lv';

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
        return $this->repository->getEnLocaleProductFlatsWithNoLvCounterParts();
    }

    protected function transformData(Collection $data): Collection
    {
        return $data->map(function ($productFlat) {
            return [
                'sku'                  => $productFlat->sku,
                'type'                 => $productFlat->type,
                'product_number'       => $productFlat->product_number,
                'name'                 => $productFlat->name,
                'short_description'    => $productFlat->short_description,
                'description'          => $productFlat->description,
                'url_key'              => $productFlat->url_key.'-lv',
                'new'                  => $productFlat->new,
                'featured'             => $productFlat->featured,
                'status'               => $productFlat->status,
                'meta_title'           => $productFlat->meta_title,
                'meta_keywords'        => $productFlat->meta_keywords,
                'meta_description'     => $productFlat->meta_description,
                'price'                => $productFlat->price,
                'special_price'        => $productFlat->special_price,
                'special_price_from'   => $productFlat->special_price_from,
                'special_price_to'     => $productFlat->special_price_to,
                'weight'               => $productFlat->weight,
                'locale'               => 'lv',
                'channel'              => $productFlat->channel,
                'attribute_family_id'  => $productFlat->attribute_family_id,
                'product_id'           => $productFlat->product_id,
                'parent_id'            => $productFlat->parent_id,
                'visible_individually' => $productFlat->visible_individually,
            ];
        });
    }

    protected function upsertData(Collection $data): void
    {
        $this->repository->upsertProductFlatDuplicates($data);
    }
}
