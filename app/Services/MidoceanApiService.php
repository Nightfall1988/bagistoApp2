<?php
namespace App\Services;
use Hitexis\Product\Models\Product;
use GuzzleHttp\Client as GuzzleClient;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Attribute\Repositories\AttributeRepository;
use Hitexis\Attribute\Repositories\AttributeOptionRepository;
use Hitexis\Product\Repositories\SupplierRepository;
use Hitexis\Product\Repositories\ProductImageRepository;
use Hitexis\Product\Repositories\ProductAttributeValueRepository;
use App\Services\CategoryImportService;
use Symfony\Component\Console\Helper\ProgressBar;
use Hitexis\Markup\Repositories\MarkupRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Illuminate\Support\Facades\DB;

class MidoceanApiService {

    protected $url;

    protected $pricesUrl;

    protected $productRepository;

    protected array $productImages;

    protected array $variantList;

    public function __construct(
        HitexisProductRepository $productRepository,
        AttributeRepository $attributeRepository,
        AttributeOptionRepository $attributeOptionRepository,
        SupplierRepository $supplierRepository,
        ProductImageRepository $productImageRepository,
        ProductAttributeValueRepository $productAttributeValueRepository,
        MarkupRepository $markupRepository,
        CategoryImportService $categoryImportService,
        protected LocaleRepository $localeRepository
    ) {
        $this->productRepository = $productRepository;
        $this->attributeOptionRepository = $attributeOptionRepository;
        $this->attributeRepository = $attributeRepository;
        $this->supplierRepository = $supplierRepository;
        $this->productImageRepository = $productImageRepository;
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->markupRepository = $markupRepository;
        $this->categoryImportService = $categoryImportService;

        // $this->url = 'https://appbagst.free.beeceptor.com/zz'; // TEST;
        $this->url = env('MIDOECAN_PRODUCTS_URL');
        $this->pricesUrl = env('MIDOECAN_PRICES_URL');
        $this->identifier = env('MIDOECAN_IDENTIFIER');
        $this->printUrl = env('MIDOECAN_PRINT_URL');
        // $this->stockUrl = env('MIDOCEAN_STOCK_DATA');
        $this->productImages = [];
        $this->globalMarkup = null;
    }

    public function getData()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'x-Gateway-APIKey' => env('MIDOECAN_API_KEY'),
        ];
    
        $this->httpClient = new GuzzleClient([
            'headers' => $headers
        ]);

        // GET PRODUCTS
        $request = $this->httpClient->get($this->url);
        $response = json_decode($request->getBody()->getContents());

        // GET PRICES
        $priceRequest = $this->httpClient->get($this->pricesUrl);
        $priceData = json_decode($priceRequest->getBody()->getContents(), true);

        // GET STOCK
        // $stockRequest = $this->httpClient->get($this->stockUrl);
        // $stockData = json_decode($stockRequest->getBody()->getContents(), true);

        $priceList = [];
        foreach ($priceData['price'] as $priceItem) {
            $sku = $priceItem['sku'];
            $price = str_replace(',', '.', $priceItem['price']);
            $priceList[$sku] = $price;
        }

        $stockList = [];
        // foreach ($stockData['stock'] as $stockItem) {
        //     $sku = $stockItem['sku'];
        //     $qty = $stockItem['qty'];
        //     $stockList[$sku] = $qty;
        // }

        $this->globalMarkup = $this->markupRepository->where('markup_type', 'global')->first();
        $tracker = new ProgressBar($this->output, count($response));
        $tracker->start();
        // SAVE PRODUCTS AND VARIANTS
        foreach ($response as $apiProduct) {

            $type = '';
            $mainVariant = $apiProduct->variants[0];
            
            // CREATE CATEGORY IF EXISTS
            $categories = [];
            if(isset($mainVariant->category_level1)) {
                $categories = $this->categoryImportService->importMidoceanData($mainVariant);
            }

            if (sizeof($apiProduct->variants) == 1) {
                $this->createSimpleProduct($mainVariant, $apiProduct, $priceList, $categories, $stockList);
                $tracker->advance();
            }

            elseif (sizeof($apiProduct->variants) > 1) {
                $this->createConfigurable($apiProduct->variants, $apiProduct, $priceList,  $categories, $stockList);
                $tracker->advance();
            }
        }
        
        $tracker->finish();
        $this->output->writeln("\nMidocean product Import finished");
    }

    public function createConfigurable($variantList, $apiProduct, $priceList,  $categories, $stockList)  {
        $colorList = [];
        $sizeList = [];
        $variants = [];
        $tempAttributes = [];
        $attributes = [];

        $productCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($variantList[0]->category_level1)) ?? ', ';
        $productSubCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($variantList[0]->category_level2)) ?? ', ';
        $locales = $this->localeRepository->get();

        foreach ($apiProduct->variants as $variant) {

            // GET VARIANT COLOR
            if (isset($variant->color_description)) {
                $result = $this->attributeOptionRepository->getOption($variant->color_description);

                if ($result != null && !in_array($result->id, $colorList)) {
                    $colorList[] = $result->id;
                }

                if ($result == null) {
                    {
                        $color = $this->attributeOptionRepository->create([
                            'admin_name' => ucfirst((string)$variant->color_description),
                            'attribute_id' => 23,
                        ]);
    
                        $colorId = $color->id;
                        $colorList[] = $colorId;
                    }
                }
            }

            // GET VARIANT SIZE
            $capacities = ['4G', '4GB', '8G', '8GB', '16G', '16GB', '32G', '32GB'];

            if (isset($variant->size)) {
                $result = $this->attributeOptionRepository->getOption($variant->size);

                if ($result != null && !in_array($result->id, $sizeList)) {
                    $sizeId = $result->id;
                    $sizeList[] = $result->id;
                }

                if ($result == null) {
                    {
                        $size = $this->attributeOptionRepository->create([
                            'admin_name' => strtoupper($variant->size),
                            'attribute_id' => 24,
                        ]);
    
                        $sizeId = $size->id;
                        $sizeList[] = $sizeId;
                    }
                }
            } elseif (sizeof(explode('-', $variant->sku)) == 3 && !in_array(explode('-', $variant->sku)[2], $capacities)) {
            

                $sizes = ['L', 'S', 'M', 'XS', 'XL', 'XXS', 'XXL', '3XS', '3XL', '4XL', '5XL','4XS', '5XS', 'XXXS', 'XXXL'];
                $sizeName = explode('-',$variant->sku)[2];

                if (in_array($sizeName, $sizes)) {
                    $result = $this->attributeOptionRepository->getOption($sizeName);

                    if ($result != null && !in_array($result->id, $sizeList)) {
                        $sizeId = $result->id;
                        $sizeList[] = $result->id;
                    }

                    if ($result == null) {
                        {
                            $size = $this->attributeOptionRepository->create([
                                'admin_name' => strtoupper($sizeName),
                                'attribute_id' => 24,
                            ]);
        
                            $sizeId = $size->id;
                            $sizeList[] = $sizeId;
                        }   
                    }
                }
            }
        }

        if (sizeof($sizeList) > 0) {
            $attributes['size'] = $sizeList;
        }

        if (sizeof($colorList) > 0) {
            $attributes['color'] = $colorList;
        }

        $product = $this->productRepository->upserts([
            "channel" => "default",
            'attribute_family_id' => '1',
            'sku' => $apiProduct->master_code,
            "type" => 'configurable',
            'super_attributes' => $attributes
        ]);

        for ($i=0; $i<sizeof($apiProduct->variants); $i++) {
            $productVariant = $this->productRepository->upserts([
                "channel" => "default",
                'attribute_family_id' => '1',
                'sku' => $apiProduct->variants[$i]->sku,
                "type" => 'simple',
                'parent_id' => $product->id
            ]);

            $sizeId = '';
            $colorId = '';
            // GET PRODUCT VARIANT COLOR
            if (isset($apiProduct->variants[$i]->color_description)) {
                $result = $this->attributeOptionRepository->getOption($apiProduct->variants[$i]->color_description);
                if ($result != null && !in_array($result->id,$tempAttributes)) {
                    $colorId = $result->id;
                }

                if ($result == null) {
                    {
                        $color = $this->attributeOptionRepository->create([
                            'admin_name' => ucfirst($apiProduct->variants[$i]->color_description),
                            'attribute_id' => 23,
                        ]);

                        $colorId = $color->id;
                    }
                }
            }

            // GET PRODUCT VARIANT SIZE
            $capacities = ['4G', '4GB', '8G', '8GB', '16G', '16GB', '32G', '32GB'];
            if (isset($apiProduct->variants[$i]->size)) {
                $result = $this->attributeOptionRepository->getOption($apiProduct->variants[$i]->size);
                if ($result != null) {
                    $sizeId = $result->id;
                }

                if ($result == null && !in_array(explode('-', $apiProduct->variants[$i]->sku)[2], $capacities)) {
                    {
                        $size = $this->attributeOptionRepository->create([
                            'admin_name' => ucfirst($apiProduct->variants[$i]->size),
                            'attribute_id' => 24,
                        ]);
    
                        $sizeId = $size->id;
                        $sizeList[] = $sizeId;
                    }
                }
            } elseif (sizeof(explode('-', $apiProduct->variants[$i]->sku)) == 3 && !in_array(explode('-', $apiProduct->variants[$i]->sku)[2], $capacities)) {
            
                $sizes = ['L', 'S', 'M', 'XS', 'XL', 'XXS', 'XXL', '3XS', '3XL', '4XL', '5XL','4XS', '5XS', 'XXXS', 'XXXL'];
                $sizeName = explode('-',$apiProduct->variants[$i]->sku)[2];
                $result = $this->attributeOptionRepository->getOption($sizeName);

                if (in_array($sizeName, $sizes)) {

                    if ($result != null) {
                        $sizeId =  $result->id;
                        $sizeList[] = $result->id;
                    }
                }

                if ($result == null || !in_array($sizeName, $sizes) && !in_array(explode('-', $apiProduct->variants[$i]->sku)[2], $capacities)) {
                    {
                        $size = $this->attributeOptionRepository->create([
                            'admin_name' => strtoupper($sizeName),
                            'attribute_id' => 24,
                        ]);

                        $sizeId = $size->id;
                        $sizeList[] = $sizeId;
                    }
                }
            }

            $images = [];

            // IMAGES
            if (isset($apiProduct->variants[$i]->digital_assets)) {
                $imageData = $this->productImageRepository->uploadImportedImagesMidocean($apiProduct->variants[$i]->digital_assets);
                $images['files'] = $imageData['fileList'];
                $tempPaths[] = $imageData['tempPaths'];
            }

            // URLKEY
            $urlKey = !isset($apiProduct->product_name) ? strtolower($apiProduct->master_code . '-' . $apiProduct->variants[$i]->sku) : strtolower($apiProduct->product_name . '-' . $apiProduct->variants[$i]->sku);
            $urlKey = trim($urlKey, '-');
            $urlKey = strtolower($urlKey);
            $urlKey = preg_replace('/\s+/', '-', $urlKey);
            $urlKey = preg_replace('/[^a-z0-9-]+/', '-', strtolower($urlKey));

            $name = $product['Name'];
            $cost = $priceList[$apiProduct->variants[$i]->sku] ?? 0;
            $cost = round($cost, 2);
            $price = $this->markupRepository->calculatePrice($cost, $this->globalMarkup);

            $variants[$productVariant->id] = [
                "sku" => $apiProduct->variants[$i]->sku,
                "name" => $apiProduct->product_name ?? $apiProduct->master_code,
                'price' => round($price, 2),
                'cost' => round($cost, 2),
                "weight" => $apiProduct->net_weight ?? 0,
                "status" => "1",
                "new" => "1",
                "visible_individually" => "0",
                "status" => "1",
                "featured" => "1",
                "guest_checkout" => "1",
                "product_number" => $apiProduct->master_id . '-' . $apiProduct->variants[$i]->sku,
                "url_key" => $urlKey,
                "short_description" => (isset($apiProduct->short_description)) ? '<p>' . $apiProduct->short_description . '</p>' : '',
                "description" => (isset($apiProduct->long_description)) ? '<p>' . $apiProduct->long_description . '</p>'  : '',
                "manage_stock" => "1",
                "inventories" => [
                  1 => 10 // $stockList[$apiProduct->variants[$i]->sku]
                ],
                'images' => $images
            ];

            if ($colorList != []) {
                $variants[$productVariant->id]['color'] = $colorId;
            }

            if ($sizeList != []) {
                $variants[$productVariant->id]['size'] = $sizeId;
            }

            $this->supplierRepository->create([
                'product_id' => $product->id,
                'supplier_code' => $this->identifier
            ]);

            $cost = $priceList[$apiProduct->variants[$i]->sku] ?? 0;
            $cost = round($cost, 2);

            $price = $this->markupRepository->calculatePrice($cost, $this->globalMarkup);
            $productVariant->markup()->attach($this->globalMarkup->id);

            $urlKey = !isset($apiProduct->product_name) ? strtolower($apiProduct->master_code . '-' . $apiProduct->variants[$i]->sku) : strtolower($apiProduct->product_name . '-' . $apiProduct->variants[$i]->sku);
            $urlKey = trim($urlKey, '-');
            $urlKey = strtolower($urlKey);
            $urlKey = preg_replace('/\s+/', '-', $urlKey);
            $urlKey = preg_replace('/[^a-z0-9-]+/', '-', strtolower($urlKey));

            $price = $this->markupRepository->calculatePrice($cost, $this->globalMarkup);

            $meta_title =  (!isset($apiProduct->product_name)) ? '' : "$apiProduct->product_name $apiProduct->product_class $apiProduct->brand";
            $meta_description = "$apiProduct->short_description";
            $meta_keywords = (!isset($apiProduct->product_name)) ? '' : "$apiProduct->product_name, $apiProduct->brand, $productCategory, $productSubCategory, $apiProduct->product_class";
            $price = $this->markupRepository->calculatePrice($cost, $this->globalMarkup);

            foreach ($locales as $localeObj) {
                $superAttributes = [
                    '_method' => 'PUT',
                    "channel" => "default",
                    "locale" => $localeObj->code,
                    'sku' => $apiProduct->variants[$i]->sku,
                    "product_number" => $apiProduct->master_id . '-' . $apiProduct->variants[$i]->sku, //
                    "name" => (!isset($apiProduct->product_name)) ? 'no name' : $apiProduct->product_name,
                    "url_key" => $urlKey,
                    "short_description" => (isset($apiProduct->short_description)) ? '<p>' . $apiProduct->short_description . '</p>' : '',
                    "description" => (isset($apiProduct->long_description)) ? '<p>' . $apiProduct->long_description . '</p>'  : '',
                    "meta_title" =>  $meta_title,
                    "meta_keywords" => $meta_keywords,
                    "meta_description" => $meta_description,
                    "material" => $materialObj->admin_name ?? '',
                    "tax_category_id" => "1",
                    "dimensions" => $dimensionsObj->admin_name ?? '',
                    'price' => round($price, 2),
                    'cost' => round($cost, 2),
                    "special_price" => "",
                    "special_price_from" => "",
                    "special_price_to" => "",
                    "new" => "1",
                    "visible_individually" => "0",
                    "status" => "1",
                    "featured" => "1",
                    "guest_checkout" => "1",
                    "manage_stock" => "1",       
                    "length" => $apiProduct->length ?? '',
                    "width" => $apiProduct->width ?? '',
                    "height" => $apiProduct->height ?? '',
                    "weight" => $apiProduct->net_weight ?? 0,
                    'categories' => $categories,
                    'images' =>  $images,
                    "inventories" => [
                        1 => 10 // $stockList[$apiProduct->variants[$i]->sku]
                      ],
                ];
            
                if ($colorId != '') {
                    $superAttributes['color'] = $colorId;
                }
    
                if ($sizeId != '') {
                    $superAttributes['size'] = $sizeId;
                }
    
                if (isset($apiProduct->dimensions)) {
                    $dimensionsObj = $this->attributeOptionRepository->getOption($apiProduct->dimensions);
                    
                    if ($dimensionsObj) {
                        $superAttributes['dimensions'] =  $dimensionsObj->admin_name;
                    }
        
                    if (!$dimensionsObj) {
                        {
                            $dimensionsObj = $this->attributeOptionRepository->create([
                                'admin_name' => ucfirst(trim($apiProduct->dimensions)),
                                'attribute_id' => 30,
                            ]);
        
                            $this->productAttributeValueRepository->upsert([
                                'product_id' => $product->id,
                                'attribute_id' => 30,
                                'locale' => $localeObj->code,
                                'channel' => null,
                                'unique_id' => implode('|', [$product->id,30]),
                                'text_value' => $dimensionsObj->admin_name ?? '',
                                'boolean_value' => null,
                                'integer_value' => null,
                                'float_value' => null,
                                'datetime_value' => null,
                                'date_value' => null,
                                'json_value' => null,
                            ], uniqueBy: ['product_id', 'attribute_id']);
        
                            $superAttributes['dimensions'] =  $dimensionsObj->admin_name;
                        }
                    }
                }
    
                if (isset($apiProduct->material)) {
                    $materialObj = $this->attributeOptionRepository->getOption($apiProduct->material);
                    if ($materialObj) {
                        $superAttributesLv['material'] =  $materialObj->admin_name;
                    }
        
                    if (!$materialObj) {
                        {
                            $materialObj = $this->attributeOptionRepository->create([
                                'admin_name' => ucfirst(trim($apiProduct->material)),
                                'attribute_id' => 29,
                            ]);
        
                            $this->productAttributeValueRepository->upsert([
                                'product_id' => $product->id,
                                'attribute_id' => 29,
                                'locale' => $localeObj->code,
                                'channel' => null,
                                'unique_id' => implode('|', [$product->id,29]),
                                'text_value' => $materialObj->admin_name,
                                'boolean_value' => null,
                                'integer_value' => null,
                                'float_value' => null,
                                'datetime_value' => null,
                                'date_value' => null,
                                'json_value' => null,
                            ], uniqueBy: ['product_id', 'attribute_id']);
        
                            $superAttributes['material'] =  $materialObj->admin_name;
                        }
                    }
                }

                $productVariant = $this->productRepository->updateToShop($superAttributes, $productVariant->id, $attribute = 'id');
                $this->markupRepository->addMarkupToPrice($productVariant,$this->globalMarkup);
            }
        }

        $productCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($apiProduct->variants[0]->category_level1)) ?? ', ';
        $productSubCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($apiProduct->variants[0]->category_level2)) ?? ', ';

        if (isset($apiProduct->product_name) && !empty($apiProduct->product_name)) {
            $meta_title = "$apiProduct->product_name $apiProduct->product_class $apiProduct->brand";
            $meta_keywords = "$apiProduct->product_name, $apiProduct->brand, $productCategory, $productSubCategory, $apiProduct->product_class";
            $urlKey = strtolower($apiProduct->product_name . '-' . $apiProduct->master_code);
        } else {
            $meta_title = "$apiProduct->master_id $apiProduct->product_class $apiProduct->brand";
            $meta_description = "$apiProduct->short_description";
            $meta_keywords = "$apiProduct->master_id, $apiProduct->brand, $productCategory, $productSubCategory, $apiProduct->product_class";
            $urlKey = strtolower($apiProduct->master_id . '-' . $apiProduct->master_code);
        }

        $urlKey = preg_replace('/\s+/', '-', $urlKey);
        $urlKey = preg_replace('/[^a-z0-9-]+/', '-', strtolower($urlKey));
        $urlKey = trim($urlKey, '-');
        $urlKey = strtolower($urlKey);
        $cost = round($cost, 2);

        $meta_description = "$apiProduct->short_description";
        $price = $this->markupRepository->calculatePrice($cost, $this->globalMarkup);

        foreach ($locales as $localeObj) {
            $superAttributes = [
                "channel" => "default",
                "locale" =>  $localeObj->code,
                'sku' => $product->sku,
                "product_number" => $apiProduct->master_id, //
                "name" => (!isset($apiProduct->product_name)) ? 'no name' : $apiProduct->product_name,
                "url_key" => $urlKey,
                "short_description" => (isset($apiProduct->short_description)) ? '<p>' . $apiProduct->short_description . '</p>' : '',
                "description" => (isset($apiProduct->long_description)) ? '<p>' . $apiProduct->long_description . '</p>'  : '',
                "meta_title" =>  $meta_title,
                "meta_keywords" => $meta_keywords,
                "meta_description" => $meta_description,
                "tax_category_id" => "1",
                'price' => round($price, 2),
                'cost' => round($cost, 2),
                "special_price" => "",
                "special_price_from" => "",
                "special_price_to" => "",
                "new" => "1",
                "visible_individually" => ($cost == 0 || !isset($apiProduct->product_name) || empty($apiProduct->product_name)) ? "0" : "1",
                "status" => "1",
                "featured" => "1",
                "guest_checkout" => "1",
                "manage_stock" => "1",       
                "length" => $apiProduct->length ?? '',
                "width" => $apiProduct->width ?? '',
                "height" => $apiProduct->height ?? '',
                "weight" => $apiProduct->net_weight ?? 0,
                'categories' => $categories,
                'images' =>  $images,
                'variants' => $variants,
            ];

            if (!empty($sizeList)) {
                $superAttributesPivot[] = [
                    'product_id'   => $product['id'],
                    'attribute_id' => 24,
                ];
        
                DB::table('product_super_attributes')->upsert(
                    $superAttributesPivot,
                    [
                        'product_id',
                        'attribute_id',
                    ],
                );
            }
    
            if (!empty($colorList)) {
                $superAttributesPivot[] = [
                    'product_id'   => $product['id'],
                    'attribute_id' => 23,
                ];
        
                DB::table('product_super_attributes')->upsert(
                    $superAttributesPivot,
                    [
                        'product_id',
                        'attribute_id',
                    ],
                );
            }
    
            if (isset($apiProduct->dimensions)) {
                $dimensionsObj = $this->attributeOptionRepository->getOption($apiProduct->dimensions);
                
                if ($dimensionsObj) {
                    $superAttributes['dimensions'] =  $dimensionsObj->admin_name;
                }
    
                if (!$dimensionsObj) {
                    {
                        $dimensionsObj = $this->attributeOptionRepository->create([
                            'admin_name' => ucfirst(trim($apiProduct->dimensions)),
                            'attribute_id' => 30,
                        ]);
    
                        $this->productAttributeValueRepository->upsert([
                            'product_id' => $product->id,
                            'attribute_id' => 30,
                            "locale" =>  $localeObj->code,
                            'channel' => null,
                            'unique_id' => implode('|', [$product->id,30]),
                            'text_value' => $dimensionsObj->admin_name ?? '',
                            'boolean_value' => null,
                            'integer_value' => null,
                            'float_value' => null,
                            'datetime_value' => null,
                            'date_value' => null,
                            'json_value' => null,
                        ], uniqueBy: ['product_id', 'attribute_id']);

                        $superAttributes['dimensions'] =  $dimensionsObj->admin_name;
                    }
                }

                if (isset($apiProduct->material)) {
                    $materialObj = $this->attributeOptionRepository->getOption($apiProduct->material);
                    if ($materialObj) {
                        $superAttributesLv['material'] =  $materialObj->admin_name;
                    }
        
                    if (!$materialObj) {
                        {
                            $materialObj = $this->attributeOptionRepository->create([
                                'admin_name' => ucfirst(trim($apiProduct->material)),
                                'attribute_id' => 29,
                            ]);
        
                            $this->productAttributeValueRepository->upsert([
                                'product_id' => $product->id,
                                'attribute_id' => 29,
                                "locale" =>  $localeObj->code,
                                'channel' => null,
                                'unique_id' => implode('|', [$product->id,29]),
                                'text_value' => $materialObj->admin_name,
                                'boolean_value' => null,
                                'integer_value' => null,
                                'float_value' => null,
                                'datetime_value' => null,
                                'date_value' => null,
                                'json_value' => null,
                            ], uniqueBy: ['product_id', 'attribute_id']);
        
                            $superAttributes['material'] =  $materialObj->admin_name;
                        }
                    }
                }
            }
            $product = $this->productRepository->updateToShop($superAttributes, $product->id, $attribute = 'id');
            $this->markupRepository->addMarkupToPrice($product,$this->globalMarkup);
        }
    }

    public function createSimpleProduct($mainVariant, $apiProduct, $priceList, $categories, $stockList) {
        $tempAttributes= [];
        $locales = $this->localeRepository->get();

        $product = $this->productRepository->upserts([
            'channel' => 'default',
            'attribute_family_id' => '1',
            'sku' => $mainVariant->sku,
            "type" => 'simple',
        ]);

        $productSku = $product->sku ?? '';
        $cost = isset($priceList[$productSku]) ? $priceList[$productSku] : 0;
        $cost = round($cost, 2);

        if ($this->globalMarkup) {
            $product->markup()->attach($this->globalMarkup->id);
        }

        $images = [];
        if (isset($mainVariant->digital_assets)) {
            $imageData = $this->productImageRepository->uploadImportedImagesMidocean($mainVariant->digital_assets, $product);
            $images['files'] = $imageData['fileList'];
            $tempPaths[] = $imageData['tempPaths'];
        }

        $productCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($apiProduct->variants[0]->category_level1)) ?? ', ';
        $productSubCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($apiProduct->variants[0]->category_level2)) ?? ', ';

        $name = $apiProduct->variants[0]->product_name ?? '';
        $productClass = $apiProduct->variants[0]->product_class ?? '';
        $brand = $apiProduct->variants[0]->brand ?? '';
        $shortDescriptions = $apiProduct->variants[0]->short_description ?? '';
        $urlKey = !isset($apiProduct->product_name) ? strtolower($apiProduct->master_code . '-' . $apiProduct->variants[0]->sku) : strtolower($apiProduct->product_name . '-' . $apiProduct->variants[0]->sku);
        $urlKey = isset($apiProduct->product_name) ? $apiProduct->product_name  . '-' . $apiProduct->master_id : $apiProduct->master_id; 
        $urlKey = preg_replace('/\s+/', '-', $urlKey);
        $urlKey = preg_replace('/[^a-z0-9-]+/', '-', strtolower($urlKey));
        $urlKey = trim($urlKey, '-');
        $urlKey = strtolower($urlKey);

        $meta_title = "$name $productClass $brand";
        $meta_description = "$shortDescriptions";
        $meta_keywords = "$name, $productClass, $brand, $productCategory, $productSubCategory";
        $price = $this->markupRepository->calculatePrice($cost, $this->globalMarkup);

        foreach ($locales as $localeObj) {
            $superAttributes = [
                "channel" => "default",
                "locale" => $localeObj->code,
                'sku' => $productSku,
                "product_number" => $apiProduct->master_id,
                "name" => (!isset($apiProduct->product_name)) ? 'no name' : $apiProduct->product_name,
                "url_key" => $urlKey,
                "short_description" => (isset($apiProduct->short_description)) ? '<p>' . $apiProduct->short_description . '</p>' : '',
                "description" => (isset($apiProduct->long_description)) ? '<p>' . $apiProduct->long_description . '</p>'  : '',
                "meta_title" => $meta_title,
                "meta_keywords" => $meta_keywords,
                "meta_description" => $meta_description,
                'price' => round($price, 2),
                'cost' => round($cost, 2),
                "tax_category_id" => "1",
                "special_price" => "",
                "special_price_from" => "",
                "special_price_to" => "",          
                "length" => $apiProduct->length ?? '',
                "width" => $apiProduct->width ?? '',
                "height" => $apiProduct->height ?? '',
                "weight" => $apiProduct->net_weight ?? 0,
                "new" => "1",
                "visible_individually" => $cost == 0 ? "0" : "1",
                "status" => "1",
                "featured" => "1",
                "guest_checkout" => "1",
                "manage_stock" => "1",
                "inventories" => [
                    1 => 10 // $stockList[$productSku]
                  ],
                'categories' => $categories,
                'images' =>  $images
            ];
    
            if (isset($apiProduct->dimensions)) {
                $dimensionsObj = $this->attributeOptionRepository->getOption($apiProduct->dimensions);
                
                if ($dimensionsObj) {
                    $superAttributes['dimensions'] =  $dimensionsObj->admin_name;
                }
    
                if (!$dimensionsObj) {
                    {
                        $dimensionsObj = $this->attributeOptionRepository->create([
                            'admin_name' => ucfirst(trim($apiProduct->dimensions)),
                            'attribute_id' => 30,
                        ]);
    
                        $this->productAttributeValueRepository->upsert([
                            'product_id' => $product->id,
                            'attribute_id' => 30,
                            'locale' => $localeObj->code,
                            'channel' => null,
                            'unique_id' => implode('|', [$product->id,30]),
                            'text_value' => $dimensionsObj->admin_name ?? '',
                            'boolean_value' => null,
                            'integer_value' => null,
                            'float_value' => null,
                            'datetime_value' => null,
                            'date_value' => null,
                            'json_value' => null,
                        ], uniqueBy: ['product_id', 'attribute_id']);
    
                        $superAttributes['dimensions'] =  $dimensionsObj->admin_name;
                    }
                }
            }
    
            if (isset($apiProduct->material)) {
                $materialObj = $this->attributeOptionRepository->getOption($apiProduct->material);
                if ($materialObj) {
                    $superAttributes['material'] =  $materialObj->admin_name;
                }
    
                if (!$materialObj) {
                    {
                        $materialObj = $this->attributeOptionRepository->create([
                            'admin_name' => ucfirst(trim($apiProduct->material)),
                            'attribute_id' => 29,
                        ]);
    
                        $this->productAttributeValueRepository->upsert([
                            'product_id' => $product->id,
                            'attribute_id' => 29,
                            'locale' => $localeObj->code,
                            'channel' => null,
                            'unique_id' => implode('|', [$product->id,29]),
                            'text_value' => $materialObj->admin_name,
                            'boolean_value' => null,
                            'integer_value' => null,
                            'float_value' => null,
                            'datetime_value' => null,
                            'date_value' => null,
                            'json_value' => null,
                        ], uniqueBy: ['product_id', 'attribute_id']);
    
                        $superAttributes['material'] =  $materialObj->admin_name;
                    }
                }
            }

            $product = $this->productRepository->updateToShop($superAttributes, $product->id, $attribute = 'id');
            $this->markupRepository->addMarkupToPrice($product,$this->globalMarkup);
        }

        $this->supplierRepository->create([
                'product_id' => $product->id,
                'supplier_code' => $this->identifier
            ]);
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }
}
