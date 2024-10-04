<?php

namespace Hitexis\Product\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Repositories\CustomerRepository;
use Hitexis\Product\Repositories\ProductAttributeValueRepository;
use Hitexis\Attribute\Repositories\AttributeRepository;
use Hitexis\Product\Repositories\SearchSynonymRepository;
use Hitexis\Product\Repositories\ElasticSearchRepository;
use Hitexis\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Admin\Http\Controllers\Catalog\ProductController;
use Illuminate\Support\Facades\Event;
use Hitexis\Product\Contracts\Product;
use Hitexis\Product\Adapters\ProductAdapter;
use Webkul\Product\Repositories\ProductRepository as WebkulProductRepository;
use Hitexis\Product\Models\Product as HitexisProductModel;
use Illuminate\Support\Facades\Cache;

class HitexisProductRepository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected AttributeRepository $attributeRepository,
        protected ProductAttributeValueRepository $productAttributeValueRepository,
        protected ElasticSearchRepository $elasticSearchRepository,
        protected SearchSynonymRepository $searchSynonymRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return HitexisProductModel::class;
    }

    /**
     * Create product.
     *
     * @return \Hitexis\Product\Contracts\Product
     */
    public function create(array $data)
    {
        // Retrieve the product type class from the configuration
        $typeClass = config('hitexis_product_types.' . $data['type'] . '.class');

        if (!$typeClass) {
            throw new \InvalidArgumentException("Product type '{$data['type']}' not found in configuration.");
        }
        
        $typeInstance = app(config('hitexis_product_types.'.$data['type'].'.class'));
        $product = $typeInstance->create($data);

        return $product;
    }

    /**
     * Create product.
     *
     * @return \Hitexis\Product\Contracts\Product
     */
    public function upserts(array $data)
    {
        $typeClass = config('product_types.' . $data['type'] . '.class');
    
        if (!$typeClass) {
            throw new \InvalidArgumentException("Product type '{$data['type']}' not found in configuration.");
        }
        
        $typeInstance = app(config('hitexis_product_types.' . $data['type'] . '.class'));
    
        $existingProduct = $this->findOneByField('sku', $data['sku']);

        if ($existingProduct) {
            $product = $this->findOneByField('sku', $existingProduct->sku);
            $product = $typeInstance->update($data,$existingProduct->id);

        } else {
            $product = $typeInstance->create($data);
        }
    
        return $product;
    }

    /**
     * Update product.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return \Hitexis\Product\Contracts\Product
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $product = $this->findOrFail($id);
        $product = $product->getTypeInstance()->update($data, $id, $attribute);
        $product->refresh();
        if (isset($data['channels'])) {
            $product['channels'] = $data['channels'];
        }

        return $product;
    }

    public function convertToHitexisProduct($product)
    {
        return new ProductAdapter($product);
    }

    public function getProductAdapter($productId)
    {
        $product = HitexisProductModel::find($productId);

        if ($product) {
            return $this->convertToHitexisProduct($product);
        }

        return null;
    }

    /**
     * Copy product.
     *
     * @param  int  $id
     * @return \Hitexis\Product\Contracts\Product
     */
    public function copy($id)
    {
        $product = $this->with([
            'attribute_family',
            'categories',
            'customer_group_prices',
            'inventories',
            'inventory_sources',
        ])->findOrFail($id);

        if ($product->parent_id) {
            throw new \Exception(trans('product::app.datagrid.variant-already-exist-message'));
        }

        return DB::transaction(function () use ($product) {
            $copiedProduct = $product->getTypeInstance()->copy();

            return $copiedProduct;
        });
    }


    /**
     * Update the specified resource in storage.
     *
     */
    public function updateToShop($data, $id)
    {
        Event::dispatch('catalog.product.update.before', $id);

        $product = $this->update($data, $id);

        Event::dispatch('catalog.product.update.after', $product);

        return $product;
    }

    /**
     * Return product by filtering through attribute values.
     *
     * @param  string  $code
     * @param  mixed  $value
     * @return \Hitexis\Product\Models\Product|null
     */
    public function findByAttributeCode($code, $value): ?HitexisProductModel
    {
        $attribute = $this->attributeRepository->findOneByField('code', $code);

        $attributeValues = $this->productAttributeValueRepository->findWhere([
            'attribute_id'          => $attribute->id,
            $attribute->column_name => $value,
        ]);

        if ($attribute->value_per_channel) {
            if ($attribute->value_per_locale) {
                $filteredAttributeValues = $attributeValues
                    ->where('channel', core()->getRequestedChannelCode())
                    ->where('locale', core()->getRequestedLocaleCode());
                if ($filteredAttributeValues->isEmpty()) {
                    $filteredAttributeValues = $attributeValues
                        ->where('channel', core()->getRequestedChannelCode())
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel());
                }
            } else {
                $filteredAttributeValues = $attributeValues
                    ->where('channel', core()->getRequestedChannelCode());
            }
        } else {
            if ($attribute->value_per_locale) {
                $filteredAttributeValues = $attributeValues
                    ->where('locale', core()->getRequestedLocaleCode());

                if ($filteredAttributeValues->isEmpty()) {
                    $filteredAttributeValues = $attributeValues
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel());
                }
            } else {
                $filteredAttributeValues = $attributeValues;
            }
        }

        $product = $filteredAttributeValues->first()?->product;

        if (isset($product) && get_class($product) == "Webkul\Product\Models\Product") {
            $product = new ProductAdapter($product);
            $product = $product->getModel();
        }

        return $product;
    }

    /**
     * Return product by filtering through attribute values.
     *
     * @param  string  $code
     * @param  mixed  $value
     * @return \Hitexis\Product\Models\Product|null
     */
    public function findWhereSimilarAttributeCode($code, $value): ?HitexisProductModel
    {
        $attribute = $this->attributeRepository->findOneByField('code', $code);

        $attributeValues = $this->productAttributeValueRepository->where('attribute_id', $attribute->id)
        ->where($attribute->column_name, 'LIKE', '%' . $value . '%')
        ->get();

        if ($attribute->value_per_channel) {
            if ($attribute->value_per_locale) {
                $filteredAttributeValues = $attributeValues
                    ->where('channel', core()->getRequestedChannelCode())
                    ->where('locale', core()->getRequestedLocaleCode());
                if ($filteredAttributeValues->isEmpty()) {
                    $filteredAttributeValues = $attributeValues
                        ->where('channel', core()->getRequestedChannelCode())
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel());
                }
            } else {
                $filteredAttributeValues = $attributeValues
                    ->where('channel', core()->getRequestedChannelCode());
            }
        } else {
            if ($attribute->value_per_locale) {
                $filteredAttributeValues = $attributeValues
                    ->where('locale', core()->getRequestedLocaleCode());

                if ($filteredAttributeValues->isEmpty()) {
                    $filteredAttributeValues = $attributeValues
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel());
                }
            } else {
                $filteredAttributeValues = $attributeValues;
            }
        }

        $product = $filteredAttributeValues->first()?->product;

        if (isset($product) && get_class($product) == "Webkul\Product\Models\Product") {
            $product = new ProductAdapter($product);
            $product = $product->getModel();
        }

        return $product;
    }
    /**
     * Retrieve product from slug without throwing an exception.
     */
    public function findBySlug(string $slug): ?HitexisProductModel
    {
        if (core()->getConfigData('catalog.products.storefront.search_mode') == 'elastic') {
            $indices = $this->elasticSearchRepository->search([
                'url_key' => $slug,
            ], [
                'type'  => '',
                'from'  => 0,
                'limit' => 1,
                'sort'  => 'id',
                'order' => 'desc',
            ]);

            return $this->find(current($indices['ids']));
        }


        return $this->findByAttributeCode('url_key', $slug);
    }

    /**
     * Retrieve product from slug.
     */
    public function findBySlugOrFail(string $slug): ?Product
    {
        $product = $this->findBySlug($slug);

        if (! $product) {
            throw (new ModelNotFoundException)->setModel(
                get_class($this->model), $slug
            );
        }

        return $product;
    }

    /**
     * Get all products.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAll(array $params = [])
    {
        if (core()->getConfigData('catalog.products.storefront.search_mode') == 'elastic') {
            return $this->searchFromElastic($params);
        }

        return $this->searchFromDatabase($params);
    }

    /**
     * Search product from database.
     *
     * @return \Illuminate\Support\Collection
     */
    public function searchFromDatabase(array $params = [])
    {
        $params = array_merge([
            'status'               => 1,
            'visible_individually' => 1,
            'url_key'              => null,
        ], $params);
    
        // Update search query for name and description
        if (!empty($params['query'])) {
            $params['name'] = $params['query'];
            $params['description'] = $params['query'];
        }
    
        // Eager load necessary relationships
        $query = $this->with([
            'attribute_family',
            'images',
            'price_indices',
            'inventory_indices',
            'reviews'
        ])->scopeQuery(function ($query) use ($params) {
            $prefix = DB::getTablePrefix();
            $customerGroup = $this->customerRepository->getCurrentGroup();
    
            $qb = $query->distinct()
                ->select('products.*')
                ->leftJoin('product_price_indices', function ($join) use ($customerGroup) {
                    $join->on('products.id', '=', 'product_price_indices.product_id')
                         ->where('product_price_indices.customer_group_id', $customerGroup->id);
                });
    
            // Subquery to get necessary attribute values
            $qb->leftJoin(DB::raw("(SELECT product_id, attribute_id, text_value, boolean_value FROM product_attribute_values) as pav"),
                          'products.id', '=', 'pav.product_id');
    
            // Filter by visible_individually attribute
            $qb->where(function ($query) {
                $query->where('pav.attribute_id', 7)
                      ->where('pav.boolean_value', 1);  // Visible individually
            });
    
            // Filter by name and description (attribute_id = 2 for name, 9/10 for descriptions)
            if (!empty($params['query'])) {
                $qb->where(function ($subQuery) use ($params) {
                    $subQuery->where('pav.attribute_id', 2)
                             ->where('pav.text_value', 'like', '%' . urldecode($params['query']) . '%')
                             ->orWhere(function ($query) use ($params) {
                                 $query->where('pav.attribute_id', 9)
                                       ->where('pav.text_value', 'like', '%' . urldecode($params['query']) . '%')
                                       ->orWhere('pav.attribute_id', 10)
                                       ->where('pav.text_value', 'like', '%' . urldecode($params['query']) . '%');
                             });
                });
            }
    
            // Sorting
            $sortOptions = $this->getSortOptions($params);
            if ($sortOptions['order'] != 'rand') {
                $attribute = $this->attributeRepository->findOneByField('code', $sortOptions['sort']);
                if ($attribute) {
                    if ($attribute->code === 'price') {
                        $qb->orderBy('product_price_indices.min_price', $sortOptions['order']);
                    } else {
                        $qb->orderBy('pav.text_value', $sortOptions['order']);
                    }
                } else {
                    $qb->orderBy('products.created_at', $sortOptions['order']);
                }
            } else {
                $qb->inRandomOrder();
            }
    
            return $qb->groupBy('products.id');
        });
    
        // Cache result if applicable
        $cacheKey = 'search_products_' . md5(serialize($params));
        return Cache::remember($cacheKey, 60, function () use ($query, $params) {
            $limit = $this->getPerPageLimit($params);
            return $query->paginate($limit);
        });
    }
    
    
    /**
     * Create product.
     *
     * @return \Hitexis\Product\Contracts\Product
     */
    public function upsertsStricker(array $data)
    {
        $typeClass = config('hitexis_product_types.' . $data['type'] . '.class');

        if (!$typeClass) {
            throw new \InvalidArgumentException("Product type '{$data['type']}' not found in configuration.");
        }
        
        $typeInstance = app(config('hitexis_product_types.' . $data['type'] . '.class'));

        $existingProduct = $this->findOneByField('sku',  $data['sku']);

        if ($existingProduct) {
            $product = $typeInstance->update($data,$existingProduct->id);
            return $product;
        } else {

            if ($data['type'] == 'configurable') {
                $product = $this->create($data);
                return $product;
            }

            elseif ($data['type'] == 'simple') {
                $product = $this->create($data);
                return $product;
            }
        }
    }


    /**
     * Search product from elastic search.
     *
     * To Do (@devansh-): Need to reduce all the request query from this repo and provide
     * good request parameter with an array type as an argument. Make a clean pull request for
     * this to have track record.
     *
     * @return \Illuminate\Support\Collection
     */
    public function searchFromElastic(array $params = [])
    {
        $currentPage = Paginator::resolveCurrentPage('page');

        $limit = $this->getPerPageLimit($params);

        $sortOptions = $this->getSortOptions($params);

        $indices = $this->elasticSearchRepository->search($params, [
            'from'  => ($currentPage * $limit) - $limit,
            'limit' => $limit,
            'sort'  => $sortOptions['sort'],
            'order' => $sortOptions['order'],
        ]);

        $query = $this->with([
            'attribute_family',
            'images',
            'videos',
            'attribute_values',
            'price_indices',
            'inventory_indices',
            'reviews',
        ])->scopeQuery(function ($query) use ($indices) {
            $qb = $query->distinct()
                ->whereIn('products.id', $indices['ids']);

            //Sort collection
            $qb->orderBy(DB::raw('FIELD(id, '.implode(',', $indices['ids']).')'));

            return $qb;
        });

        $items = $indices['total'] ? $query->get() : [];

        $results = new LengthAwarePaginator($items, $indices['total'], $limit, $currentPage, [
            'path'  => request()->url(),
            'query' => $params,
        ]);

        return $results;
    }

    /**
     * Fetch per page limit from toolbar helper. Adapter for this repository.
     */
    public function getPerPageLimit(array $params): int
    {
        return product_toolbar()->getLimit($params);
    }

    /**
     * Fetch sort option from toolbar helper. Adapter for this repository.
     */
    public function getSortOptions(array $params): array
    {
        return product_toolbar()->getOrder($params);
    }

    /**
     * Returns product's super attribute with options.
     *
     * @param  \Hitexis\Product\Contracts\Product  $product
     * @return \Illuminate\Support\Collection
     */
    public function getSuperAttributes($product)
    {
        $superAttributes = [];

        foreach ($product->super_attributes as $key => $attribute) {
            $superAttributes[$key] = $attribute->toArray();

            foreach ($attribute->options as $option) {
                $superAttributes[$key]['options'][] = [
                    'id'           => $option->id,
                    'admin_name'   => $option->admin_name,
                    'sort_order'   => $option->sort_order,
                    'swatch_value' => $option->swatch_value,
                ];
            }
        }

        return $superAttributes;
    }

    /**
     * Return category product maximum price.
     *
     * @param  int  $categoryId
     * @return float
     */
    public function getMaxPrice($params = [])
    {
        $customerGroup = $this->customerRepository->getCurrentGroup();

        $query = $this->model
            ->leftJoin('product_price_indices', 'products.id', 'product_price_indices.product_id')
            ->leftJoin('product_categories', 'products.id', 'product_categories.product_id')
            ->where('product_price_indices.customer_group_id', $customerGroup->id);

        if (! empty($params['category_id'])) {
            $query->where('product_categories.category_id', $params['category_id']);
        }

        return $query->max('min_price') ?? 0;
    }

    public function getCategoryProducts($params = [])
    {
        $customerGroup = $this->customerRepository->getCurrentGroup();

        $query = $this->with([
            'attribute_family',
            'images',
            'videos',
            'attribute_values',
            'price_indices',
            'inventory_indices',
            'reviews',
        ])->scopeQuery(function ($query) use ($params, $customerGroup) {
            $prefix = DB::getTablePrefix();
    
            // Start the query
            $qb = $query->distinct()
                ->select('products.*')
                ->leftJoin('product_price_indices', function ($join) use ($customerGroup) {
                    $join->on('products.id', '=', 'product_price_indices.product_id')
                        ->where('product_price_indices.customer_group_id', $customerGroup->id);
                })
                ->leftJoin('product_categories', 'products.id', 'product_categories.product_id');
    
            // Filter by attributes like descriptions
            $descriptionAlias1 = 'description_9_product_attribute_values';
            $descriptionAlias2 = 'description_10_product_attribute_values';
    
            $qb->leftJoin('product_attribute_values as ' . $descriptionAlias1, function ($join) use ($descriptionAlias1) {
                $join->on('products.id', '=', $descriptionAlias1 . '.product_id')
                    ->where($descriptionAlias1 . '.attribute_id', 9);
            });
    
            $qb->leftJoin('product_attribute_values as ' . $descriptionAlias2, function ($join) use ($descriptionAlias2) {
                $join->on('products.id', '=', $descriptionAlias2 . '.product_id')
                    ->where($descriptionAlias2 . '.attribute_id', 10);
            });
    
            // Handle visibility of individual products
            $visibleIndividuallyAlias = 'visible_individually_product_attribute_values';
            $qb->leftJoin('product_attribute_values as ' . $visibleIndividuallyAlias, function ($join) use ($visibleIndividuallyAlias) {
                $join->on('products.id', '=', $visibleIndividuallyAlias . '.product_id')
                    ->where($visibleIndividuallyAlias . '.attribute_id', 7)
                    ->where($visibleIndividuallyAlias . '.boolean_value', 1);
            });
    
            // Join for product name
            $nameAlias = 'name_product_attribute_values';
            $qb->leftJoin('product_attribute_values as ' . $nameAlias, function ($join) use ($nameAlias) {
                $join->on('products.id', '=', $nameAlias . '.product_id')
                    ->where($nameAlias . '.attribute_id', 2);
            });
    
            // Filter by category
            if (!empty($params['category_id'])) {
                $qb->where('product_categories.category_id', $params['category_id']);
            }
    
            // Filter by color
            if (!empty($params['color'])) {
                $colorIds = explode(',', $params['color']);
                $colorAlias = 'color_product_attribute_values';
    
                $qb->leftJoin('product_attribute_values as ' . $colorAlias, function ($join) use ($colorAlias) {
                    $join->on('products.id', '=', $colorAlias . '.product_id')
                        ->where($colorAlias . '.attribute_id', 23);
                });
    
                // For variants, check if parent_id exists
                $qb->where(function ($query) use ($colorAlias, $colorIds) {
                    $query->whereIn($colorAlias . '.integer_value', $colorIds)
                        ->orWhere(function ($query) use ($colorAlias, $colorIds) {
                            $query->whereIn($colorAlias . '.integer_value', $colorIds);
                        });
                });
            }
    
            // Filter by size
            if (!empty($params['size'])) {
                $sizeIds = explode(',', $params['size']);
                $sizeAlias = 'size_product_attribute_values';
    
                $qb->leftJoin('product_attribute_values as ' . $sizeAlias, function ($join) use ($sizeAlias) {
                    $join->on('products.id', '=', $sizeAlias . '.product_id')
                        ->where($sizeAlias . '.attribute_id', 24);
                });
    
                // For variants, handle size filtering
                $qb->where(function ($query) use ($sizeAlias, $sizeIds) {
                    $query->whereIn($sizeAlias . '.integer_value', $sizeIds)
                        ->orWhereIn($sizeAlias . '.integer_value', $sizeIds);
                });
            }
    
            // Filter by price range
            if (!empty($params['price'])) {
                $priceRange = explode(',', $params['price']);
                $minPrice = isset($priceRange[0]) ? floatval($priceRange[0]) : 0;
                $maxPrice = isset($priceRange[1]) ? floatval($priceRange[1]) : null;
    
                $qb->where(function ($query) use ($minPrice, $maxPrice) {
                    $query->where('product_price_indices.min_price', '>=', $minPrice);
                    if ($maxPrice !== null) {
                        $query->where('product_price_indices.min_price', '<=', $maxPrice);
                    }
                });
            }
    
            // Ensure only visible products are included
            $qb->where($visibleIndividuallyAlias . '.boolean_value', 1);
    
            // Sorting
            $sortOptions = $this->getSortOptions($params);
            if ($sortOptions['order'] != 'rand') {
                $attribute = $this->attributeRepository->findOneByField('code', $sortOptions['sort']);
                if ($attribute) {
                    if ($attribute->code === 'price') {
                        $qb->orderBy('product_price_indices.min_price', $sortOptions['order']);
                    } else {
                        $alias = 'sort_product_attribute_values';
                        $qb->leftJoin('product_attribute_values as ' . $alias, function ($join) use ($alias, $attribute) {
                            $join->on('products.id', '=', $alias . '.product_id')
                                ->where($alias . '.attribute_id', $attribute->id)
                                ->where($alias . '.channel', core()->getRequestedChannelCode())
                                ->where($alias . '.locale', core()->getRequestedLocaleCode());
                        })->orderBy($alias . '.' . $attribute->column_name, $sortOptions['order']);
                    }
                } else {
                    $qb->orderBy('products.created_at', $sortOptions['order']);
                }
            } else {
                return $qb->inRandomOrder();
            }
    
            return $qb->groupBy('products.id');
        });
    
        $limit = $this->getPerPageLimit($params);
    
        return $query->paginate($limit);
    }    
}
