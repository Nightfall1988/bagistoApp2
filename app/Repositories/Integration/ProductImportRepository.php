<?php

namespace App\Repositories\Integration;

use App\Repositories\BaseImportRepository;
use Hitexis\Attribute\Models\AttributeOption;
use Hitexis\PrintCalculator\Models\PositionPrintTechniques;
use Hitexis\PrintCalculator\Models\PrintingPositions;
use Hitexis\PrintCalculator\Models\PrintManipulation;
use Hitexis\PrintCalculator\Models\PrintTechnique;
use Hitexis\PrintCalculator\Models\PrintTechniqueVariableCosts;
use Hitexis\PrintCalculator\Models\ProductPrintData;
use Hitexis\PrintCalculator\Models\ProductUrlImages;
use Hitexis\Product\Models\Product;
use Hitexis\Product\Models\ProductAttributeValue;
use Hitexis\Product\Models\ProductCategory;
use Hitexis\Product\Models\ProductFlat;
use Hitexis\Product\Models\ProductInventory;
use Hitexis\Product\Models\ProductInventoryIndex;
use Hitexis\Product\Models\ProductPriceIndex;
use Hitexis\Product\Models\ProductSupplier;
use Illuminate\Support\Collection;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Category\Models\CategoryTranslation;
use Webkul\Core\Models\Channel;
use Webkul\Customer\Models\CustomerGroup;

class ProductImportRepository extends BaseImportRepository
{
    public function getProducts(Collection $products): Collection
    {
        return Product::whereIn('sku', $products->pluck('sku'))->get()->keyBy('sku');
    }

    public function getCategories(): Collection
    {
        return CategoryTranslation::where('locale', 'en')->get()->keyBy('name');
    }

    public function getCustomerGroups(): Collection
    {
        return CustomerGroup::all();
    }

    public function getProductFlatsFromProductNumbers(Collection $productFlats): Collection
    {
        return ProductFlat::whereIn('product_number', $productFlats->pluck('product_number'))->get(['product_number', 'product_id'])->keyBy('product_number');
    }

    public function getProductPrintDataFromProductFlats(Collection $productFlats): Collection
    {
        return ProductPrintData::whereIn('product_id', $productFlats->pluck('product_id'))->get(['id', 'product_id'])->keyBy('product_id');
    }

    public function getProductPrintDataFromProducts(Collection $products): Collection
    {
        return ProductPrintData::whereIn('product_id', $products->pluck('id'))->get(['id', 'product_id'])->keyBy('product_id');
    }

    public function getAttributeOptionsByName(): Collection
    {
        return AttributeOption::all()->keyBy('admin_name');
    }

    public function getPrintingPositionsFromPrintData(Collection $printData): Collection
    {
        return PrintingPositions::whereIn('product_print_data_id', $printData->pluck('id'))->get(['id', 'product_print_data_id', 'position_id'])->groupBy('product_print_data_id')
            ->map(function ($positions) {
                return $positions->keyBy('position_id');
            });
    }

    public function getDefaultAttributeFamily(): AttributeFamily
    {
        return AttributeFamily::where('code', 'default')->first();
    }

    public function getDefaultChannel(): Channel
    {
        return Channel::where('code', 'default')->first();
    }

    public function getAllPrintManipulations(): Collection
    {
        return PrintManipulation::all()->keyBy('code');
    }

    public function upsertProducts(Collection $products): void
    {
        $this->handleUpsert(function () use ($products) {
            $products->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                Product::upsert(
                    $chunk->all(),
                    ['sku'],
                    ['type', 'attribute_family_id']
                );
            });
        });
    }

    public function upsertVariants(Collection $variants): void
    {
        $this->handleUpsert(function () use ($variants) {
            $variants->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                Product::upsert(
                    $chunk->all(),
                    ['sku'],
                    ['type', 'parent_id', 'attribute_family_id']
                );
            });
        });
    }

    public function upsertProductCategories(Collection $productCategories): void
    {
        $this->handleUpsert(function () use ($productCategories) {
            $productCategories->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductCategory::upsert(
                    $chunk->all(),
                    ['product_id', 'category_id'],
                );
            });
        });
    }

    public function upsertProductFlats(Collection $productFlats, ?int $customBatchSize = null): void
    {
        $batchSize = $customBatchSize ?? $this->upsertBatchSize;

        $this->handleUpsert(function () use ($productFlats, $batchSize) {
            $productFlats->chunk($batchSize)->each(function (Collection $chunk) {
                ProductFlat::upsert(
                    $chunk->all(),
                    ['product_id', 'channel', 'locale'],
                    ['sku', 'type', 'product_number', 'name', 'short_description', 'description', 'weight', 'url_key', 'meta_title', 'meta_description', 'attribute_family_id', 'visible_individually']
                );
            });
        });
    }

    public function upsertProductFlatPrices(Collection $productFlats): void
    {
        $this->handleUpsert(function () use ($productFlats) {
            $productFlats->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductFlat::upsert(
                    $chunk->all(),
                    ['product_id', 'channel', 'locale'],
                    ['price']
                );
            });
        });
    }

    public function upsertAttributeOptions(Collection $attributeOptions): void
    {
        $this->handleUpsert(function () use ($attributeOptions) {
            $attributeOptions->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                AttributeOption::upsert(
                    $chunk->all(),
                    ['admin_name', 'attribute_id']
                );
            });
        });
    }

    public function upsertProductAttributeValues(Collection $productAttributeValues): void
    {
        $this->handleUpsert(function () use ($productAttributeValues) {
            $productAttributeValues->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductAttributeValue::upsert(
                    $chunk->all(),
                    ['product_id', 'attribute_id'],
                    ['text_value', 'channel', 'locale', 'integer_value', 'boolean_value','float_value'],
                );
            });
        });
    }

    public function upsertProductAttributeValuePrices(Collection $productAttributeValuePrices): void
    {
        $this->handleUpsert(function () use ($productAttributeValuePrices) {
            $productAttributeValuePrices->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductAttributeValue::upsert(
                    $chunk->all(),
                    ['product_id', 'attribute_id'],
                    ['text_value', 'channel', 'locale']
                );
            });
        });
    }

    public function upsertProductPriceIndices(Collection $productPriceIndices): void
    {
        $this->handleUpsert(function () use ($productPriceIndices) {
            $productPriceIndices->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductPriceIndex::upsert(
                    $chunk->all(),
                    ['product_id', 'customer_group_id'],
                    ['channel_id', 'min_price', 'regular_min_price', 'max_price', 'regular_max_price']
                );
            });
        });
    }

    public function upsertPrintManipulations(Collection $printManipulations): void
    {
        $this->handleUpsert(function () use ($printManipulations) {
            $printManipulations->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                PrintManipulation::upsert(
                    $chunk->all(),
                    ['code'],
                    ['currency', 'pricelist_valid_from', 'pricelist_valid_until', 'description', 'price']
                );
            });
        });
    }

    public function upsertPrintTechniques(Collection $printTechniques): void
    {
        $this->handleUpsert(function () use ($printTechniques) {
            $printTechniques->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                PrintTechnique::upsert(
                    $chunk->all(),
                    ['technique_id'],
                    ['description', 'pricing_type', 'setup', 'setup_repeat', 'next_colour_cost_indicator']
                );
            });
        });
    }

    public function upsertPrintVariableCosts(Collection $printVariableCosts): void
    {
        $this->handleUpsert(function () use ($printVariableCosts) {
            $printVariableCosts->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                PrintTechniqueVariableCosts::upsert(
                    $chunk->all(),
                    ['print_technique_id', 'range_id'],
                    ['area_from', 'area_to', 'pricing_data']
                );
            });
        });
    }

    public function upsertProductPrintData(Collection $productPrintData): void
    {
        $this->handleUpsert(function () use ($productPrintData) {
            $productPrintData->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductPrintData::upsert(
                    $chunk->all(),
                    ['product_id', 'print_manipulation_id'],
                    ['print_template']
                );
            });
        });
    }

    public function upsertPrintingPositions(Collection $productPrintData): void
    {
        $this->handleUpsert(function () use ($productPrintData) {
            $productPrintData->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                PrintingPositions::upsert(
                    $chunk->all(),
                    ['product_print_data_id', 'position_id'],
                    ['print_size_unit', 'max_print_size_height', 'max_print_size_width', 'rotation', 'print_position_type']
                );
            });
        });
    }

    public function upsertPositionPrintTechniques(Collection $productPrintData): void
    {
        $this->handleUpsert(function () use ($productPrintData) {
            $productPrintData->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                PositionPrintTechniques::upsert(
                    $chunk->all(),
                    ['printing_position_id', 'print_technique_id'],
                    ['default', 'max_colours']
                );
            });
        });
    }

    public function upsertProductInventories(Collection $productInventories): void
    {
        $this->handleUpsert(function () use ($productInventories) {
            $productInventories->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductInventory::upsert(
                    $chunk->all(),
                    ['product_id'],
                    ['qty', 'vendor_id', 'inventory_source_id']
                );
            });
        });
    }

    public function upsertProductInventoryIndices(Collection $productInventories): void
    {
        $this->handleUpsert(function () use ($productInventories) {
            $productInventories->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductInventoryIndex::upsert(
                    $chunk->all(),
                    ['product_id'],
                    ['qty', 'channel_id']
                );
            });
        });
    }

    public function upsertProductURLImages(Collection $productURLImages): void
    {
        $this->handleUpsert(function () use ($productURLImages) {
            $productURLImages->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductUrlImages::upsert(
                    $chunk->all(),
                    ['product_id', 'position'],
                    ['position', 'type']
                );
            });
        });
    }

    public function upsertSupplierCodes(Collection $supplierCodes): void
    {
        $this->handleUpsert(function () use ($supplierCodes) {
            $supplierCodes->chunk($this->upsertBatchSize)->each(function (Collection $chunk) {
                ProductSupplier::upsert(
                    $chunk->all(),
                    ['product_id'],
                    ['supplier_code']
                );
            });
        });
    }
}
