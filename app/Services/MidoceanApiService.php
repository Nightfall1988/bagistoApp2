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
        CategoryImportService $categoryImportService
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
        // dd(env('MIDOECAN_PRODUCTS_URL'));
        $this->url = env('MIDOECAN_PRODUCTS_URL');
        $this->pricesUrl = env('MIDOECAN_PRICES_URL');
        $this->identifier = env('MIDOECAN_IDENTIFIER');
        $this->printUrl = env('MIDOECAN_PRINT_URL');
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

        $priceList = [];
        foreach ($priceData['price'] as $priceItem) {
            $sku = $priceItem['sku'];
            $price = str_replace(',', '.', $priceItem['price']);
            $priceList[$sku] = $price;
        }

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
                $this->createSimpleProduct($mainVariant, $apiProduct, $priceList, $categories);
                $tracker->advance();
            }

            elseif (sizeof($apiProduct->variants) > 1) {
                $this->createConfigurable($apiProduct->variants, $apiProduct, $priceList,  $categories);
                $tracker->advance();
            }
        }
        
        $tracker->finish();
        $this->output->writeln("\nMidocean product Import finished");
    }

    public function createConfigurable($variantList, $apiProduct, $priceList,  $categories)  {
        $colorList = [];
        $sizeList = [];
        $variants = [];
        $tempAttributes = [];
        $attributes = [];

        $productCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($variantList[0]->category_level1)) ?? ', ';
        $productSubCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($variantList[0]->category_level2)) ?? ', ';
        
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
            

                $sizes = ['L', 'S', 'M', 'XS', 'XL', 'XXS', 'XXL', '3XS', '3XL', 'XXXS', 'XXXL'];
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
            
                $sizes = ['L', 'S', 'M', 'XS', 'XL', 'XXS', 'XXL', '3XS', '3XL', 'XXXS', 'XXXL'];
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
            $urlKey = strtolower($apiProduct->product_name . '-' . $apiProduct->variants[$i]->sku);
            $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
            $urlKey = trim($urlKey, '-');
            $urlKey = strtolower($urlKey);
            $name = $product['Name'];
            $cost = $priceList[$apiProduct->variants[$i]->sku] ?? 0;

            $variants[$productVariant->id] = [
                "sku" => $apiProduct->variants[$i]->sku,
                "name" => $apiProduct->product_name,
                "cost" => $cost,
                "price" => $cost,
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
                  1 => "10"
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
            $urlKey = strtolower($apiProduct->product_name . '-' . $apiProduct->variants[$i]->sku);
            $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
            $urlKey = trim($urlKey, '-');
            $urlKey = strtolower($urlKey);

            $superAttributes = [
                '_method' => 'PUT',
                "channel" => "default",
                "locale" => "en",
                'sku' => $apiProduct->variants[$i]->sku,
                "product_number" => $apiProduct->master_id . '-' . $apiProduct->variants[$i]->sku, //
                "name" => (!isset($apiProduct->product_name)) ? 'no name' : $apiProduct->product_name,
                "url_key" => $urlKey,
                "short_description" => (isset($apiProduct->short_description)) ? '<p>' . $apiProduct->short_description . '</p>' : '',
                "description" => (isset($apiProduct->long_description)) ? '<p>' . $apiProduct->long_description . '</p>'  : '',
                "meta_title" =>  "",
                "meta_keywords" => "",
                "meta_description" => "",
                'price' => $cost,
                'cost' => $cost,
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
            ];

        
            if ($colorId != '') {
                $superAttributes['color'] = $colorId;
            }

            if ($sizeId != '') {
                $superAttributes['size'] = $sizeId;
            }

            $productVariant = $this->productRepository->updateToShop($superAttributes, $productVariant->id, $attribute = 'id');
        }

        $productCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($apiProduct->variants[0]->category_level1)) ?? ', ';
        $productSubCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($apiProduct->variants[0]->category_level2)) ?? ', ';

        $urlKey = strtolower($apiProduct->product_name . '-' . $product->sku);
        $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
        $urlKey = trim($urlKey, '-');
        $urlKey = strtolower($urlKey);

        $meta_title = "$apiProduct->product_name $apiProduct->product_class $apiProduct->brand";
        $meta_description = "$apiProduct->short_description";
        $meta_keywords = "$apiProduct->product_name, $apiProduct->brand, $productCategory, $productSubCategory, $apiProduct->product_class";

        $superAttributes = [
            "channel" => "default",
            "locale" => "en",
            'sku' => $product->sku,
            "product_number" => $apiProduct->master_id, //
            "name" => (!isset($apiProduct->product_name)) ? 'no name' : $apiProduct->product_name,
            "url_key" => $urlKey, //
            "short_description" => (isset($apiProduct->short_description)) ? '<p>' . $apiProduct->short_description . '</p>' : '',
            "description" => (isset($apiProduct->long_description)) ? '<p>' . $apiProduct->long_description . '</p>'  : '',
            "meta_title" =>  $meta_title,
            "meta_keywords" => $meta_keywords,
            "meta_description" => $meta_description,
            'price' => $cost,
            'cost' => $cost,
            "special_price" => "",
            "special_price_from" => "",
            "special_price_to" => "",
            "new" => "1",
            "visible_individually" => "1",
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
            'variants' => $variants
        ];

        $product = $this->productRepository->updateToShop($superAttributes, $product->id, $attribute = 'id');
        $this->markupRepository->addMarkupToPrice($product, $this->globalMarkup);
        return $product;
    }

    public function createSimpleProduct($mainVariant, $apiProduct, $priceList, $categories) {

        $product = $this->productRepository->upserts([
            'channel' => 'default',
            'attribute_family_id' => '1',
            'sku' => $mainVariant->sku,
            "type" => 'simple',
        ]);

        $productSku = $product->sku ?? '';
        $cost = isset($priceList[$productSku]) ? $priceList[$productSku] : 0;
        $product->markup()->attach($this->globalMarkup->id);

        $urlKey = isset($apiProduct->product_name) ? $apiProduct->product_name  . '-' . $apiProduct->master_id : $apiProduct->master_id; 
        $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
        $urlKey = trim($urlKey, '-');
        $urlKey = strtolower($urlKey);

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

        $meta_title = "$name $productClass $brand";
        $meta_description = "$shortDescriptions";
        $meta_keywords = "$name, $productClass, $brand, $productCategory, $productSubCategory";

        $superAttributes = [
            "channel" => "default",
            "locale" => "en",
            'sku' => $productSku,
            "product_number" => $apiProduct->master_id,
            "name" => (!isset($apiProduct->product_name)) ? 'no name' : $apiProduct->product_name,
            "url_key" => (!isset($apiProduct->product_name)) ? '' : $urlKey,
            "short_description" => (isset($apiProduct->short_description)) ? '<p>' . $apiProduct->short_description . '</p>' : '',
            "description" => (isset($apiProduct->long_description)) ? '<p>' . $apiProduct->long_description . '</p>'  : '',
            "meta_title" => $meta_title,
            "meta_keywords" => $meta_keywords,
            "meta_description" => $meta_description,
            'price' => $cost,
            'cost' => $cost,
            "special_price" => "",
            "special_price_from" => "",
            "special_price_to" => "",          
            "length" => $apiProduct->length ?? '',
            "width" => $apiProduct->width ?? '',
            "height" => $apiProduct->height ?? '',
            "weight" => $apiProduct->net_weight ?? 0,
            "new" => "1",
            "visible_individually" => "1",
            "status" => "1",
            "featured" => "1",
            "guest_checkout" => "1",
            "manage_stock" => "1",
            "inventories" => [
                1 => "100"
            ],
            'categories' => $categories,
            'images' =>  $images
        ];

        $this->supplierRepository->create([
                'product_id' => $product->id,
                'supplier_code' => $this->identifier
            ]);

        $product = $this->productRepository->updateToShop($superAttributes, $product->id, $attribute = 'id');
        $this->markupRepository->addMarkupToPrice($product, $this->globalMarkup);
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }
}
