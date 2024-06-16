<?php
namespace Hitexis\Product\Adapters;

use Hitexis\Product\Contracts\Product as ProductContract;
use Webkul\Product\Models\Product as WebkulProductModel;
use Hitexis\Product\Models\Product as HitexisProduct;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductAdapter implements ProductContract
{
    protected $product;

    public function __construct(WebkulProductModel $product)
    {
        $this->product = $product;
    }

    public function getModel(): HitexisProduct
    {
        $hitexisProduct = new HitexisProduct();

        $hitexisProduct->id =  $this->product->id;
        $hitexisProduct->sku =  $this->product->sku;
        $hitexisProduct->url_key =  $this->product->url_key;
        $hitexisProduct->visible_individually =  $this->product->visible_individually;
        $hitexisProduct->status =  $this->product->status;
        $hitexisProduct->type =  $this->product->type;
        $hitexisProduct->attribute_family =  $this->product->attribute_family;
        return $hitexisProduct;
    }

    public function __get($property)
    {
        return $this->product->{$property};
    }

    public function getId()
    {
        return $this->product->id;
    }

    public function getName()
    {
        return $this->product->name;
    }

    public function getUrlKey()
    {
        return $this->product->url_key;
    }

    public function getVisibleIndividually()
    {
        return $this->product->visible_individually;
    }

    public function getStatus()
    {
        return $this->product->status;
    }

    public function product_flats(): HasMany
    {
        return $this->product->product_flats();
    }

    public function parent(): BelongsTo
    {
        return $this->product->parent();
    }

    public function attribute_family(): BelongsTo
    {
        return $this->product->attribute_family();
    }

    public function super_attributes(): BelongsToMany
    {
        return $this->product->super_attributes();
    }

    public function attribute_values(): HasMany
    {
        return $this->product->attribute_values();
    }

    public function customer_group_prices(): HasMany
    {
        return $this->product->customer_group_prices();
    }

    public function catalog_rule_prices(): HasMany
    {
        return $this->product->catalog_rule_prices();
    }

    public function price_indices(): HasMany
    {
        return $this->product->price_indices();
    }

    public function inventory_indices(): HasMany
    {
        return $this->product->inventory_indices();
    }

    public function categories(): BelongsToMany
    {
        return $this->product->categories();
    }

    public function images(): HasMany
    {
        return $this->product->images();
    }

    public function videos(): HasMany
    {
        return $this->product->videos();
    }

    public function reviews(): HasMany
    {
        return $this->product->reviews();
    }

    public function approvedReviews(): HasMany
    {
        return $this->product->approvedReviews();
    }

    public function inventory_sources(): BelongsToMany
    {
        return $this->product->inventory_sources();
    }

    public function inventory_source_qty($inventorySourceId)
    {
        return $this->product->inventory_source_qty($inventorySourceId);
    }

    public function inventories(): HasMany
    {
        return $this->product->inventories();
    }

    public function ordered_inventories(): HasMany
    {
        return $this->product->ordered_inventories();
    }

    public function variants(): HasMany
    {
        return $this->product->variants();
    }

    public function grouped_products(): HasMany
    {
        return $this->product->grouped_products();
    }

    public function downloadable_samples(): HasMany
    {
        return $this->product->downloadable_samples();
    }

    public function downloadable_links(): HasMany
    {
        return $this->product->downloadable_links();
    }

    public function bundle_options(): HasMany
    {
        return $this->product->bundle_options();
    }

    public function related_products(): BelongsToMany
    {
        return $this->product->related_products();
    }

    public function up_sells(): BelongsToMany
    {
        return $this->product->up_sells();
    }

    public function cross_sells(): BelongsToMany
    {
        return $this->product->cross_sells();
    }

    public function isSaleable(): bool
    {
        return $this->product->isSaleable();
    }

    public function isStockable(): bool
    {
        return $this->product->isStockable();
    }

    public function totalQuantity(): int
    {
        return $this->product->totalQuantity();
    }

    public function haveSufficientQuantity(int $qty): bool
    {
        return $this->product->haveSufficientQuantity($qty);
    }

    public function getTypeInstance()
    {
        return $this->product->getTypeInstance();
    }

    public function getBaseImageUrlAttribute()
    {
        return $this->product->getBaseImageUrlAttribute();
    }

    public function getAttribute($key)
    {
        return $this->product->getAttribute($key);
    }

    public function getEditableAttributes($group = null, $skipSuperAttribute = true)
    {
        return $this->product->getEditableAttributes($group, $skipSuperAttribute);
    }

    public function getCustomAttributeValue($attribute)
    {
        return $this->product->getCustomAttributeValue($attribute);
    }

    public function attributesToArray(): array
    {
        return $this->product->attributesToArray();
    }

    public function checkInLoadedFamilyAttributes()
    {
        return $this->product->checkInLoadedFamilyAttributes();
    }

    public function newEloquentBuilder($query)
    {
        return $this->product->newEloquentBuilder($query);
    }

    public function wholesales(): BelongsToMany
    {
        return $this->product->wholesales();
    }

    public function supplier(): BelongsToMany
    {
        return $this->product->supplier();
    }
}
