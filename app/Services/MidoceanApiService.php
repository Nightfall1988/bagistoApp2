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
        CategoryImportService $categoryImportService
    ) {
        $this->productRepository = $productRepository;
        $this->attributeOptionRepository = $attributeOptionRepository;
        $this->attributeRepository = $attributeRepository;
        $this->supplierRepository = $supplierRepository;
        $this->productImageRepository = $productImageRepository;
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->categoryImportService = $categoryImportService;

        $this->url = env('MIDOECAN_PRODUCTS_URL');
        $this->pricesUrl = env('MIDOECAN_PRICES_URL');
        $this->identifier = env('MIDOECAN_IDENTIFIER');
        $this->productImages = [];
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
            }

            elseif (sizeof($apiProduct->variants) > 1) {
                $this->createConfigurable($apiProduct->variants, $apiProduct, $priceList,  $categories);
            }
        }        
    }

    public function createConfigurable($variantList, $apiProduct, $priceList,  $categories)  {
        $color = [];
        $size = [];
        $variants = [];
        $tempAttributes = [];
        $attributes = [];

        foreach ($apiProduct->variants as $variant) {

            // GET VARIANT COLOR
            if (isset($variant->color_group)) {
                $result = $this->attributeOptionRepository->getOption($variant->color_group);
                if ($result != null && !in_array($result->id, $color)) {
                    $color[] = $result->id;
                }
            }

            // GET VARIANT SIZE
            if (isset($variant->size)) {
                $result = $this->attributeOptionRepository->getOption($variant->size);
                if ($result != null && !in_array($result->id, $size)) {
                    $size[] = $result->id;
                }
            }
        }

        if (sizeof($size) > 0) {
            $attributes['size'] = $size;
        }

        if (sizeof($color) > 0) {
            $attributes['color'] = $color;
        }

        $product = $this->productRepository->upsert([
            'attribute_family_id' => '1',
            'sku' => $apiProduct->master_code,
            "type" => 'configurable',
            'super_attributes' => $attributes
        ]);

        for ($i=0; $i<sizeof($apiProduct->variants); $i++) {
            $productVariant = $this->productRepository->upsert([
                'attribute_family_id' => '1',
                'sku' => $apiProduct->variants[$i]->sku,
                "type" => 'simple',
                'parent_id' => $product->id
            ]);

            $sizeId = '';
            $colorId = '';
            // GET PRODUCT VARIANT COLOR AND SIZE
            if (isset($apiProduct->variants[$i]->color_group)) {
                $color = $this->attributeOptionRepository->getOption($apiProduct->variants[$i]->color_group);
                if ($color && !in_array($color->id,$tempAttributes)) {
                    $colorId = $color->id;
                    $tempAttributes[] = $colorId;
                }

            }



            if (isset($apiProduct->variants[$i]->size)) {
                $size = $this->attributeOptionRepository->getOption($apiProduct->variants[$i]->size);
                if ($size && !in_array($size->id,$tempAttributes)) {
                    $sizeId = $size->id;
                    $tempAttributes[] = $sizeId;
                }
            }

            $images = [];
            $imageData = $this->productImageRepository->uploadImportedImagesMidocean($apiProduct->variants[$i]->digital_assets);
            $images['files'] = $imageData['fileList'];
            $tempPaths[] = $imageData['tempPaths'];

            $urlKey = strtolower($apiProduct->product_name . '-' . $product->sku);
            $search = ['.', '\'', ' ', '"', ','];
            $replace = '-';
            $name = $product['Name'];
            $urlKey = strtolower(str_replace($search, $replace, $name));
            $urlKey = strtolower($apiProduct->product_name . '-' . $apiProduct->variants[$i]->sku);
            $price = $priceList[$apiProduct->variants[$i]->sku] ?? 0;

            $variants[$productVariant->id] = [
                "sku" => $apiProduct->variants[$i]->sku,
                "name" => $apiProduct->product_name,
                "price" => $price,
                "weight" => $apiProduct->net_weight ?? 0,
                "status" => "1",
                "new" => "1",
                "visible_individually" => "1",
                "status" => "1",
                "featured" => "1",
                "guest_checkout" => "1",
                "product_number" => $apiProduct->master_id,
                "url_key" => $urlKey,
                "short_description" => (isset($apiProduct->short_description)) ? '<p>' . $apiProduct->short_description . '</p>' : '',
                "description" => (isset($apiProduct->long_description)) ? '<p>' . $apiProduct->long_description . '</p>'  : '',
                "manage_stock" => "1",
                "inventories" => [
                  1 => "10"
                ],
                'images' => $images
            ];

            if ($color != []) {
                $variants[$productVariant->id]['color'] = $colorId;
            }

            if ($size != []) {
                $variants[$productVariant->id]['size'] = $sizeId;
            }

            $this->supplierRepository->create([
                'product_id' => $product->id,
                'supplier_code' => $this->identifier
            ]);

            $price = $priceList[$apiProduct->variants[$i]->sku] ?? 0;

            $urlKey = strtolower($apiProduct->product_name . '-' . $product->sku);
            $search = ['.', '\'', ' ', '"', ','];
            $replace = '-';
            $name = $product['Name'];
            $urlKey = strtolower(str_replace($search, $replace, $name));

            $superAttributes = [
                '_method' => 'PUT',
                "channel" => "default",
                "locale" => "en",
                'sku' => $apiProduct->variants[$i]->sku,
                "product_number" => $apiProduct->master_id, //
                "name" => (!isset($apiProduct->product_name)) ? 'no name' : $apiProduct->product_name,
                "url_key" => $urlKey,
                "short_description" => (isset($apiProduct->short_description)) ? '<p>' . $apiProduct->short_description . '</p>' : '',
                "description" => (isset($apiProduct->long_description)) ? '<p>' . $apiProduct->long_description . '</p>'  : '',
                "meta_title" => "",
                "meta_keywords" => "",
                "meta_description" => "",
                "meta_description" => "",
                "meta_description" => "",       
                'price' => $price,
                'cost' => '',
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

        
        if ($colorId != '') {
            $superAttributes['color'] = $colorId;
        }

        if ($sizeId != '') {
            $superAttributes['size'] = $sizeId;
        }

        $productVariant = $this->productRepository->updateToShop($superAttributes, $productVariant->id, $attribute = 'id');

        }

        $urlKey = strtolower($apiProduct->product_name . '-' . $product->sku);

        $superAttributes = [
            "channel" => "default",
            "locale" => "en",
            'sku' => $product->sku,
            "product_number" => $apiProduct->master_id, //
            "name" => (!isset($apiProduct->product_name)) ? 'no name' : $apiProduct->product_name,
            "url_key" => $urlKey, //
            "short_description" => (isset($apiProduct->short_description)) ? '<p>' . $apiProduct->short_description . '</p>' : '',
            "description" => (isset($apiProduct->long_description)) ? '<p>' . $apiProduct->long_description . '</p>'  : '',
            "meta_title" => "",
            "meta_keywords" => "",
            "meta_description" => "",
            "meta_description" => "",
            "meta_description" => "",       
            'price' => $price,
            'cost' => '',
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

        return $product;
    }

    public function createSimpleProduct($mainVariant, $apiProduct, $priceList, $categories) {

        $product = $this->productRepository->upsert([
            'attribute_family_id' => '1',
            'sku' => $mainVariant->sku,
            "type" => 'simple',
        ]);

        $productSku = $product->sku ?? '';
        $price = isset($priceList[$productSku]) ? $priceList[$productSku] : 0;

        $replace = '-';
        $search = ['.', '\'', ' ', '"']; 
        $urlKey = isset($apiProduct->product_name) ? $apiProduct->product_name  . '-' . $apiProduct->master_id : $apiProduct->master_id; 
        $urlKey = strtolower(str_replace($search, $replace, $urlKey));        
        
        $images = [];
        $imageData = $this->productImageRepository->uploadImportedImagesMidocean($mainVariant->digital_assets, $product);
        $images['files'] = $imageData['fileList'];
        $tempPaths[] = $imageData['tempPaths'];

        $superAttributes = [
            "channel" => "default",
            "locale" => "en",
            'sku' => $productSku,
            "product_number" => $apiProduct->master_id,
            "name" => (!isset($apiProduct->product_name)) ? 'no name' : $apiProduct->product_name,
            "url_key" => (!isset($apiProduct->product_name)) ? '' : $urlKey,
            "short_description" => (isset($apiProduct->short_description)) ? '<p>' . $apiProduct->short_description . '</p>' : '',
            "description" => (isset($apiProduct->long_description)) ? '<p>' . $apiProduct->long_description . '</p>'  : '',
            "meta_title" => "",
            "meta_keywords" => "",
            "meta_description" => "",
            'price' => $price,
            'cost' => '',
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

        $this->productRepository->updateToShop($superAttributes, $product->id, $attribute = 'id');
    }
}
