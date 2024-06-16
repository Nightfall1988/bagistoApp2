<?php
namespace Hitexis\Product\Contracts;

interface Product
{
    public function getId();

    public function getName();

    public function getUrlKey();

    public function getVisibleIndividually();

    public function getStatus();

    public function product_flats();

    public function parent();

    public function attribute_family();

    public function super_attributes();

    public function attribute_values();

    public function customer_group_prices();

    public function catalog_rule_prices();

    public function price_indices();

    public function inventory_indices();

    public function categories();

    public function images();

    public function videos();

    public function reviews();

    public function approvedReviews();

    public function inventory_sources();

    public function inventory_source_qty($inventorySourceId);

    public function inventories();

    public function ordered_inventories();

    public function variants();

    public function grouped_products();

    public function downloadable_samples();

    public function downloadable_links();

    public function bundle_options();

    public function related_products();

    public function up_sells();

    public function cross_sells();

    public function isSaleable();

    public function isStockable();

    public function totalQuantity();

    public function haveSufficientQuantity(int $qty);

    public function getTypeInstance();

    public function getBaseImageUrlAttribute();

    public function getAttribute($key);

    public function getEditableAttributes($group = null, $skipSuperAttribute = true);

    public function getCustomAttributeValue($attribute);

    public function attributesToArray();

    public function checkInLoadedFamilyAttributes();

    public function newEloquentBuilder($query);

    public function wholesales();

    public function supplier();
}
