<?php
namespace Hitexis\Product\Models;

use Illuminate\Database\Eloquent\Model as BaseProduct;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Hitexis\Product\Contracts\Product as ProductContract;


use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Shetabit\Visitor\Traits\Visitable;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Hitexis\Attribute\Models\AttributeProxy;
use Hitexis\Attribute\Repositories\AttributeRepository;
use Webkul\CatalogRule\Models\CatalogRuleProductPriceProxy;
use Webkul\Category\Models\CategoryProxy;
use Hitexis\Inventory\Models\InventorySourceProxy;
use Webkul\Product\Database\Eloquent\Builder;
use Hitexis\Product\Database\Factories\ProductFactory;
use Hitexis\Product\Type\AbstractType;
use Hitexis\Wholesale\Models\WholesaleProxy;
use Hitexis\PrintCalculator\Models\PrintTechniqueProxy;
use Hitexis\PrintCalculator\Models\PrintManipulationProxy;
use Hitexis\Markup\Models\MarkupProxy;


class Product extends BaseProduct implements ProductContract
{
    use HasFactory, Visitable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'type',
        'attribute_family_id',
        'sku',
        'parent_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'additional' => 'array',
    ];

    /**
     * The type of product.
     *
     * @var \Hitexis\Product\Type\AbstractType
     */
    protected $typeInstance;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getUrlKey()
    {
        return $this->url_key;
    }

    public function getVisibleIndividually()
    {
        return $this->visible_individually;
    }

    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Get the product flat entries that are associated with product.
     * May be one for each locale and each channel.
     */
    public function product_flats(): HasMany
    {
        return $this->hasMany(ProductFlatProxy::modelClass(), 'product_id');
    }

    /**
     * Get the product that owns the product.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Get the product attribute family that owns the product.
     */
    public function attribute_family(): BelongsTo
    {
        return $this->belongsTo(AttributeFamilyProxy::modelClass());
    }

    /**
     * The super attributes that belong to the product.
     */
    public function super_attributes(): BelongsToMany
    {
        return $this->belongsToMany(AttributeProxy::modelClass(), 'product_super_attributes');
    }

    /**
     * Get the product attribute values that owns the product.
     */
    public function attribute_values(): HasMany
    {
        return $this->hasMany(ProductAttributeValueProxy::modelClass());
    }

    /**
     * Get the product customer group prices that owns the product.
     */
    public function customer_group_prices(): HasMany
    {
        return $this->hasMany(ProductCustomerGroupPriceProxy::modelClass());
    }

    /**
     * Get the product customer group prices that owns the product.
     */
    public function catalog_rule_prices(): HasMany
    {
        return $this->hasMany(CatalogRuleProductPriceProxy::modelClass());
    }

    /**
     * Get the price indices that owns the product.
     */
    public function price_indices(): HasMany
    {
        return $this->hasMany(ProductPriceIndexProxy::modelClass());
    }

    /**
     * Get the inventory indices that owns the product.
     */
    public function inventory_indices(): HasMany
    {
        return $this->hasMany(ProductInventoryIndexProxy::modelClass());
    }

    /**
     * The categories that belong to the product.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CategoryProxy::modelClass(), 'product_categories');
    }

    /**
     * The images that belong to the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImageProxy::modelClass(), 'product_id')
            ->orderBy('position');
    }

    /**
     * The videos that belong to the product.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideoProxy::modelClass(), 'product_id')
            ->orderBy('position');
    }

    /**
     * Get the product reviews that owns the product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReviewProxy::modelClass());
    }

    /**
     * Get the approved product reviews.
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved');
    }

    /**
     * The inventory sources that belong to the product.
     */
    public function inventory_sources(): BelongsToMany
    {
        return $this->belongsToMany(InventorySourceProxy::modelClass(), 'product_inventories')
            ->withPivot('id', 'qty');
    }

    /**
     * Get inventory source quantity.
     *
     * @return bool
     */
    public function inventory_source_qty($inventorySourceId)
    {
        return $this->inventories()
            ->where('inventory_source_id', $inventorySourceId)
            ->sum('qty');
    }

    /**
     * The inventories that belong to the product.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(ProductInventoryProxy::modelClass(), 'product_id');
    }

    /**
     * The ordered inventories that belong to the product.
     */
    public function ordered_inventories(): HasMany
    {
        return $this->hasMany(ProductOrderedInventoryProxy::modelClass(), 'product_id');
    }

    /**
     * Get the product variants that owns the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * Get the grouped products that owns the product.
     */
    public function grouped_products(): HasMany
    {
        return $this->hasMany(ProductGroupedProductProxy::modelClass());
    }

    /**
     * The images that belong to the product.
     */
    public function downloadable_samples(): HasMany
    {
        return $this->hasMany(ProductDownloadableSampleProxy::modelClass());
    }

    /**
     * The images that belong to the product.
     */
    public function downloadable_links(): HasMany
    {
        return $this->hasMany(ProductDownloadableLinkProxy::modelClass());
    }

    /**
     * Get the bundle options that owns the product.
     */
    public function bundle_options(): HasMany
    {
        return $this->hasMany(ProductBundleOptionProxy::modelClass());
    }

    /**
     * The related products that belong to the product.
     */
    public function related_products(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'product_relations', 'parent_id', 'child_id');
    }

    /**
     * The up sells that belong to the product.
     */
    public function up_sells(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'product_up_sells', 'parent_id', 'child_id');
    }

    /**
     * The cross sells that belong to the product.
     */
    public function cross_sells(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'product_cross_sells', 'parent_id', 'child_id');
    }

    /**
     * Is saleable.
     *
     * @param  string  $key
     *
     * @throws \Exception
     */
    public function isSaleable(): bool
    {
        return $this->getTypeInstance()
            ->isSaleable();
    }

    /**
     * Is stockable.
     *
     *
     * @throws \Exception
     */
    public function isStockable(): bool
    {
        return $this->getTypeInstance()
            ->isStockable();
    }

    /**
     * Total quantity.
     *
     *
     * @throws \Exception
     */
    public function totalQuantity(): int
    {
        return $this->getTypeInstance()
            ->totalQuantity();
    }

    /**
     * Have sufficient quantity.
     *
     *
     * @throws \Exception
     */
    public function haveSufficientQuantity(int $qty): bool
    {
        return $this->getTypeInstance()
            ->haveSufficientQuantity($qty);
    }

    /**
     * Get type instance.
     *
     *
     * @throws \Exception
     */
    public function getTypeInstance(): AbstractType
    {
        if ($this->typeInstance) {
            return $this->typeInstance;
        }

        $this->typeInstance = app(config('hitexis_product_types.'.$this->type.'.class'));

        if (! $this->typeInstance instanceof AbstractType) {
            throw new Exception("Please ensure the product type '{$this->type}' is configured in your application.");
        }

        $this->typeInstance->setProduct($this);

        return $this->typeInstance;
    }

    /**
     * The images that belong to the product.
     *
     * @return string
     */
    public function getBaseImageUrlAttribute()
    {
        $image = $this->images->first();

        return $image->url ?? null;
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (! method_exists(static::class, $key)
            && ! in_array($key, [
                'pivot',
                'parent_id',
                'attribute_family_id',
            ])
            && ! isset($this->attributes[$key])
        ) {
            if (isset($this->id)) {
                $attribute = $this->checkInLoadedFamilyAttributes()->where('code', $key)->first();

                $this->attributes[$key] = $this->getCustomAttributeValue($attribute);

                return $this->getAttributeValue($key);
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * Retrieve product attributes.
     *
     * @param  Group  $group
     * @param  bool  $skipSuperAttribute
     *
     * @throws \Exception
     */
    public function getEditableAttributes($group = null, $skipSuperAttribute = true): Collection
    {
        return $this->getTypeInstance()
            ->getEditableAttributes($group, $skipSuperAttribute);
    }

    /**
     * Get an product attribute value.
     *
     * @return mixed
     */
    public function getCustomAttributeValue($attribute)
    {
        if (! $attribute) {
            return;
        }

        $locale = core()->getRequestedLocaleCodeInRequestedChannel();

        $channel = core()->getRequestedChannelCode();

        if (empty($this->attribute_values->count())) {
            $this->load('attribute_values');
        }

        if ($attribute->value_per_channel) {
            if ($attribute->value_per_locale) {
                $attributeValue = $this->attribute_values
                    ->where('channel', $channel)
                    ->where('locale', $locale)
                    ->where('attribute_id', $attribute->id)
                    ->first();

                if (empty($attributeValue[$attribute->column_name])) {
                    $attributeValue = $this->attribute_values
                        ->where('channel', core()->getDefaultChannelCode())
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel())
                        ->where('attribute_id', $attribute->id)
                        ->first();
                }
            } else {
                $attributeValue = $this->attribute_values
                    ->where('channel', $channel)
                    ->where('attribute_id', $attribute->id)
                    ->first();
            }
        } else {
            if ($attribute->value_per_locale) {
                $attributeValue = $this->attribute_values
                    ->where('locale', $locale)
                    ->where('attribute_id', $attribute->id)
                    ->first();

                if (empty($attributeValue[$attribute->column_name])) {
                    $attributeValue = $this->attribute_values
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel())
                        ->where('attribute_id', $attribute->id)
                        ->first();
                }
            } else {
                $attributeValue = $this->attribute_values
                    ->where('attribute_id', $attribute->id)
                    ->first();
            }
        }

        return $attributeValue[$attribute->column_name] ?? $attribute->default_value;
    }

    /**
     * Attributes to array.
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        $hiddenAttributes = $this->getHidden();

        if (isset($this->id)) {
            $familyAttributes = $this->checkInLoadedFamilyAttributes();

            foreach ($familyAttributes as $attribute) {
                if (in_array($attribute->code, $hiddenAttributes)) {
                    continue;
                }

                $attributes[$attribute->code] = $this->getCustomAttributeValue($attribute);
            }
        }

        return $attributes;
    }

    /**
     * Check in loaded family attributes.
     */
    public function checkInLoadedFamilyAttributes(): object
    {
        return core()->getSingletonInstance(AttributeRepository::class)
            ->getFamilyAttributes($this->attribute_family);
    }

    /**
     * Overrides the default Eloquent query builder.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Webkul\Product\Database\Eloquent\Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }

    /**
     * Get the wholesale options.
     */
    public function wholesales(): BelongsToMany
    {
        return $this->belongsToMany(WholesaleProxy::modelClass(), 'wholesale_product', 'product_id', 'wholesale_id');
    }

    /**
     * Get the supplier.
     */
    public function supplier(): HasOne
    {
        return $this->hasOne(ProductSupplierProxy::modelClass(),'product_id');
    }

    /**
     * Get the markup.
     */
    public function markup(): belongsToMany
    {
        return $this->belongsToMany(MarkupProxy::modelClass(),'markup_product', 'product_id', 'markup_id');
    }

    /**
     * Get print techniques.
     */
    public function print_techniques(): HasMany
    {
        return $this->hasMany(PrintTechniqueProxy::modelClass());
    }

    public function print_manipulations()
    {
        return $this->belongsToMany(PrintManipulationProxy::class, 'print_technique_manipulation');
    }

    public function getColors()
    {
        $attributeCode = 'color';

        // Retrieve the super attribute by code
        $colorAttribute = $this->super_attributes->firstWhere('code', $attributeCode);

        if (!$colorAttribute) {
            return collect();
        }

        $allColors = $colorAttribute->options;
        $variantColors = $this->variants->map(function ($variant) use ($attributeCode) {
            return $variant->{$attributeCode};
        })->unique();

        return $variantColors;
    }
}