<?php

namespace App\Console\Commands\Population;

use App\Repositories\Integration\ProductImportRepository;
use App\Repositories\Population\PopulationRepository;
use Hitexis\Product\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PopulateXDConnectConfigurableProductFlatsCommand extends AbstractPopulateCommand
{
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
    protected $signature = 'populate:xdconnect-configurable-product-flats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates configurable product flats for XDConnect';

    /**
     * Execute the console command.
     */
    protected function getData(): Collection
    {
        return Product::where('type', 'configurable')
            ->whereDoesntHave('product_flats')
            ->whereHas('variants', function ($query) {
                $query->has('product_flats');
            })
            ->with(['variants.product_flats', 'variants.attribute_values', 'variants.categories', 'variants.image_urls'])
            ->has('variants', '>', 1)
            ->get();
    }

    protected function transformData(Collection $data): Collection
    {
        return collect($data)->map(function ($row) {
            $variant = $row->variants->first() ?? null;
            $productFlat = $variant->product_flats->first();

            if ($variant) {
                return [
                    'product_flat'      => $this->mapProductFlat($row->sku, $productFlat, $row),
                    'attribute_values'  => $this->mapAttributeValues($row, $variant, $row->sku),
                    'categories'        => $this->mapCategories($row, $variant),
                    'variant_attributes'=> $this->removeVisibleIndividuallyFromVariants($row),
                    'image_url'         => $this->addImageFromFirstVariant($row, $variant),
                ];
            }

            return null;
        })->filter();
    }

    private function mapCategories($row, $variant): array
    {
        $categories = [];
        foreach ($variant->categories as $category) {
            $categories[] = [
                'product_id' => $row->id,
                'category_id'=> $category->id,
            ];
        }

        return $categories;
    }

    private function mapProductFlat($sku, $productFlat, $row): array
    {
        return [
            'product_flat'=> [
                'sku'                       => $sku,
                'type'                      => 'configurable',
                'name'                      => $productFlat->name,
                'short_description'         => '<p>'.$productFlat->short_description.'</p>',
                'description'               => '<p>'.$productFlat->description.'</p>',
                'weight'                    => $productFlat->weight,
                'url_key'                   => Str::slug($sku),
                'meta_title'                => $productFlat->meta_title,
                'meta_description'          => $productFlat->meta_description,
                'product_id'                => $row->id,
                'locale'                    => $productFlat->locale,
                'price'                     => $productFlat->price,
                'new'                       => $productFlat->new,
                'featured'                  => $productFlat->featured,
                'status'                    => $productFlat->status,
                'channel'                   => $productFlat->channel,
                'attribute_family_id'       => $productFlat->attribute_family_id,
                'visible_individually'      => 1, ],
        ];
    }

    private function mapAttributeValues($row, $variant, $sku): array
    {
        $attributeValues = [];
        foreach ($variant->attribute_values as $attributeValue) {
            $overriddenTextValue = null;
            $overriddenBoolValue = null;
            $overriddenIntValue = null;
            if ($attributeValue->attribute_id == 1) {
                $overriddenTextValue = $sku;
            } elseif ($attributeValue->attribute_id == 3) {
                $overriddenTextValue = Str::slug($sku);
            }
            $attributeValues[] = [
                'attribute_id'  => $attributeValue->attribute_id,
                'product_id'    => $row->id,
                'text_value'    => $overriddenTextValue ?? $attributeValue->text_value,
                'integer_value' => $overriddenIntValue ?? $attributeValue->integer_value,
                'boolean_value' => $overriddenBoolValue ?? $attributeValue->boolean_value,
                'channel'       => $attributeValue->channel,
                'locale'        => $attributeValue->locale,
                'unique_id'     => 'default|en|'.$row->id.'|'.$attributeValue->attribute_id,
            ];
        }

        return $attributeValues;
    }
    const VISIBLE_INDIVIDUALLY_ATTRIBUTE_ID = 7;

    private function removeVisibleIndividuallyFromVariants($row): array
    {
        $attributeValues = [];

        foreach ($row->variants as $variant) {
            $visibleIndividuallyAttributes = $variant->attribute_values->filter(function($attributeValue) {
                return $attributeValue->attribute_id == self::VISIBLE_INDIVIDUALLY_ATTRIBUTE_ID;
            });

            foreach ($visibleIndividuallyAttributes as $attributeValue) {
                $attributeValues[] = [
                    'attribute_id'  => self::VISIBLE_INDIVIDUALLY_ATTRIBUTE_ID,
                    'product_id'    => $variant->id,
                    'text_value'    => null,
                    'integer_value' => null,
                    'boolean_value' => 0,
                    'channel'       => $attributeValue->channel ?? 'default',
                    'locale'        => $attributeValue->locale ?? 'en',
                    'unique_id'     => 'default|'.$attributeValue->locale.'|'.$variant->id.'|'.self::VISIBLE_INDIVIDUALLY_ATTRIBUTE_ID,
                ];
            }
        }

        return $attributeValues;
    }
    private function addImageFromFirstVariant($row, $variant): array|null
    {
        if(isset($variant->image_urls[0])){
            return [
                'url'       => $variant->image_urls[0]->url,
                'product_id'=> $row->id,
                'position'  => 1,
                'type'      => $variant->type,
            ];
        }
        return null;
    }

    protected function upsertData(Collection $data): void
    {
        $this->productImportRepository->upsertProductFlats($data->pluck('product_flat')->flatten(1), 100);
        $this->productImportRepository->upsertProductAttributeValues($data->pluck('attribute_values')->flatten(1));
        $this->productImportRepository->upsertProductAttributeValues($data->pluck('variant_attributes')->flatten(1));
        $this->productImportRepository->upsertProductCategories($data->pluck('categories')->flatten(1));
        $this->productImportRepository->upsertProductURLImages($data->pluck('image_url')->filter());
    }
}
