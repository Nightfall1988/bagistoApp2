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
            'visible_individually' => 1, // Ensures only visible products
            'url_key'              => null,
        ], $params);
    
        // If a search query is provided, use it for name and description search
        if (!empty($params['query'])) {
            $searchQuery = urldecode($params['query']);
        }
    
        // Eager load necessary relationships
        $query = $this->with([
            'attribute_family',
            'images',
            'price_indices',
            'inventory_indices',
            'reviews',
        ])->scopeQuery(function ($query) use ($params, $searchQuery) {
            $prefix = DB::getTablePrefix();
            $customerGroup = $this->customerRepository->getCurrentGroup();
    
            $qb = $query->distinct()
                ->select('products.*')
                ->join('product_price_indices', function ($join) use ($customerGroup) {
                    $join->on('products.id', '=', 'product_price_indices.product_id')
                         ->where('product_price_indices.customer_group_id', $customerGroup->id);
                })
                // Join for product names (attribute_id = 2 is for name)
                ->join('product_attribute_values as pav_name', function ($join) {
                    $join->on('products.id', '=', 'pav_name.product_id')
                         ->where('pav_name.attribute_id', 2); // Attribute ID 2 is for name
                })
                // Join for product descriptions (attribute_id = 9 and 10 for short/long descriptions)
                ->join('product_attribute_values as pav_description', function ($join) {
                    $join->on('products.id', '=', 'pav_description.product_id')
                         ->where(function ($query) {
                             $query->where('pav_description.attribute_id', 9) // Short description
                                   ->orWhere('pav_description.attribute_id', 10); // Full description
                         });
                })
                // Join to filter by visibility (attribute_id = 7 for visible_individually)
                ->join('product_attribute_values as pav_visibility', function ($join) {
                    $join->on('products.id', '=', 'pav_visibility.product_id')
                         ->where('pav_visibility.attribute_id', 7) // Attribute ID 7 is for visible_individually
                         ->where('pav_visibility.boolean_value', 1); // Ensure visibility
                })
                // Ensure only products with attribute_id 7 set to 1 are included
                ->where('pav_visibility.boolean_value', 1); // Always gather results where attribute_id = 7 is set to 1
    
            // Search by name or description
            if (!empty($searchQuery)) {
                $qb->where(function ($subQuery) use ($searchQuery) {
                    $subQuery->where('pav_name.text_value', 'like', '%' . $searchQuery . '%')
                             ->orWhere('pav_description.text_value', 'like', '%' . $searchQuery . '%');
                });
            }
    
            // Sorting logic
            $sortOptions = $this->getSortOptions($params);
            if ($sortOptions['order'] != 'rand') {
                $attribute = $this->attributeRepository->findOneByField('code', $sortOptions['sort']);
                if ($attribute) {
                    if ($attribute->code === 'price') {
                        $qb->orderBy('product_price_indices.min_price', $sortOptions['order']);
                    } else {
                        $qb->orderBy('pav_name.text_value', $sortOptions['order']); // Sort by name by default
                    }
                } else {
                    $qb->orderBy('products.created_at', $sortOptions['order']); // Sort by creation date if no other sorting is specified
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
            ->join('product_price_indices', 'products.id', 'product_price_indices.product_id')
            ->join('product_categories', 'products.id', 'product_categories.product_id')
            ->where('product_price_indices.customer_group_id', $customerGroup->id);

        if (! empty($params['category_id'])) {
            $query->where('product_categories.category_id', $params['category_id']);
        }

        return $query->max('min_price') ?? 0;
    }

    public function getCategoryProducts($params = [])
    {
        $customerGroup = $this->customerRepository->getCurrentGroup();
    
        // Start the query with eager loading
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
    
            // Start with distinct products
            $qb = $query->distinct()
                ->select('products.*')
                ->join('product_price_indices', function ($join) use ($customerGroup) {
                    $join->on('products.id', '=', 'product_price_indices.product_id')
                        ->where('product_price_indices.customer_group_id', $customerGroup->id);
                })
                ->join('product_categories', 'products.id', '=', 'product_categories.product_id');
    
            // **Filter by category**
            if (!empty($params['category_id'])) {
                $qb->where('product_categories.category_id', $params['category_id']);
            }
    
            // **Join Product Attributes (optional)**
            // Use a single query instead of multiple joins for fetching attributes
            $attributeValuesAlias = 'product_attribute_values';
            $qb->leftJoin('product_attribute_values as ' . $attributeValuesAlias, 'products.id', '=', $attributeValuesAlias . '.product_id');
    
            // Handle visibility of individual products
            $qb->where($attributeValuesAlias . '.attribute_id', 7)
                ->where($attributeValuesAlias . '.boolean_value', 1);
    
            // **Filter by Color**
            if (!empty($params['color'])) {
                $colorIds = explode(',', $params['color']);
                $qb->whereIn($attributeValuesAlias . '.integer_value', $colorIds)
                    ->where($attributeValuesAlias . '.attribute_id', 23);  // Assuming 23 is for color attribute
            }
    
            // **Filter by Size**
            if (!empty($params['size'])) {
                $sizeIds = explode(',', $params['size']);
                $qb->whereIn($attributeValuesAlias . '.integer_value', $sizeIds)
                    ->where($attributeValuesAlias . '.attribute_id', 24);  // Assuming 24 is for size attribute
            }
    
            // **Filter by Price Range**
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
    
            // **Sorting**
            $sortOptions = $this->getSortOptions($params);
            if ($sortOptions['order'] != 'rand') {
                $attribute = $this->attributeRepository->findOneByField('code', $sortOptions['sort']);
                if ($attribute) {
                    if ($attribute->code === 'price') {
                        $qb->orderBy('product_price_indices.min_price', $sortOptions['order']);
                    } else {
                        $qb->leftJoin('product_attribute_values as sort_product_attribute_values', function ($join) use ($attribute) {
                            $join->on('products.id', '=', 'sort_product_attribute_values.product_id')
                                ->where('sort_product_attribute_values.attribute_id', $attribute->id)
                                ->where('sort_product_attribute_values.channel', core()->getRequestedChannelCode())
                                ->where('sort_product_attribute_values.locale', core()->getRequestedLocaleCode());
                        })
                        ->orderBy('sort_product_attribute_values.' . $attribute->column_name, $sortOptions['order']);
                    }
                } else {
                    $qb->orderBy('products.created_at', $sortOptions['order']);
                }
            } else {
                $qb->inRandomOrder();
            }
    
            return $qb->groupBy('products.id');
        });
    
        // Limit for pagination
        $limit = $this->getPerPageLimit($params);
    
        return $query->paginate($limit);
    }
    
}
