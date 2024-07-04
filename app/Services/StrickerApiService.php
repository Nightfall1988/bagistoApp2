<?php

namespace App\Services;

use Hitexis\Product\Models\Product;
use GuzzleHttp\Client as GuzzleClient;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Attribute\Repositories\AttributeRepository;
use Hitexis\Attribute\Repositories\AttributeOptionRepository;
use Hitexis\Product\Repositories\SupplierRepository;
use Hitexis\Product\Repositories\ProductImageRepository;
use App\Services\CategoryImportService;
use App\Services\CategoryMapper;

require 'CategoryMapper.php';

class StrickerApiService {

    protected $url;

    protected $pricesUrl;

    protected $productRepository;

    public function __construct(
        HitexisProductRepository $productRepository,
        AttributeRepository $attributeRepository,
        AttributeOptionRepository $attributeOptionRepository,
        SupplierRepository $supplierRepository,
        ProductImageRepository $productImageRepository,
        CategoryImportService $categoryImportService,
        CategoryMapper $categoryMapper,
        
    ) {
        $this->productRepository = $productRepository;
        $this->attributeOptionRepository = $attributeOptionRepository;
        $this->attributeRepository = $attributeRepository;
        $this->supplierRepository = $supplierRepository;
        $this->productImageRepository = $productImageRepository;
        $this->categoryImportService = $categoryImportService;
        $this->authUrl = env('STRICKER_AUTH_URL') . env('STRICKER_AUTH_TOKEN');
        $this->url = env('STRICKER_PRODUCTS_URL');
        $this->optionalsUrl = env('STRICKER_OPTIONALS_URL');
        $this->identifier = env('STRICKER_IDENTIFIER');
        $this->categoryMapper = $categoryMapper;
        // TEST DATA
        // $this->url = 'https://appbagst.free.beeceptor.com';
        // $this->optionalsUrl = 'https://appbagst.free.beeceptor.com/op';
    }
    
    public function getData()
    {
        ini_set('memory_limit', '512M');
        $headers = [
            'Content-Type' => 'application/json',
        ];
    
        $this->httpClient = new GuzzleClient([
            'headers' => $headers
        ]);

        $request = $this->httpClient->get($this->authUrl);
        $authToken = json_decode($request->getBody()->getContents())->Token;

        $this->url = $this->url . $authToken . '&lang=en';
        $this->optionalsUrl = $this->optionalsUrl . $authToken . '&lang=en';

        // // GET PRODUCTS
        // $request = $this->httpClient->get($this->url);
        // $productsData = json_decode($request->getBody()->getContents(), true);

        // // GET OPTIONALS
        // $optionalsData = $this->httpClient->get($this->optionalsUrl);
        // $optionalsData = json_decode($optionalsData->getBody()->getContents(), true);

        // TESTING DATA - RESPONSE FROM JSON FILED
        $jsonP = file_get_contents('storage\app\private\productstest.json');
        $productsData = json_decode($jsonP, true);
        $jsonO = file_get_contents('storage\app\private\optionalstest.json');
        $optionalsData = json_decode($jsonO, true);

        $products = $this->getProducts($productsData, $optionalsData);
        $this->updateProducts($products);
    }

    public function getProducts($productsData, $optionalsData)
    {
        $products = [];
        foreach ($productsData['Products'] as $product) {
            $products[$product['ProdReference']]['product'] = $product;
        }

        $optionals = [];
        foreach ($optionalsData['OptionalsComplete'] as $optionals) {
            if (array_key_exists($optionals['ProdReference'], $products)) {
                $products[$optionals['ProdReference']]['optionals'][] = $optionals;
            }
        }

        return $products;
    }

    public function updateProducts($products)
    {            
        $productReferences = [];
        $groupedOptionals = [];
        $images = [];

        foreach ($products as $prodReference => $value) {
            if (isset($value['optionals']) && sizeof($value['optionals']) > 1) {
                $this->createConfigurable($value);
            } else {
                $this->createSimple($value);
            }
        }
    }

    public function createConfigurable($productData) {

        $colorList = [];
        $sizeList = [];
        $colorIds = [];
        $sizeIds = [];

        $mainProductOptionals = $productData['optionals'][0];

            if ($productData['product']['HasColors']) {
                $colorNameList = explode(', ', $productData['product']['Colors']);
                foreach ( $colorNameList as $colorName) {
                    $colorObj = $this->attributeOptionRepository->getOption(ucfirst(trim($colorName)));
                    if ( $colorObj) {
                        $colorIds[] = $colorObj->id;
                    }
                }
            }

            if ($productData['product']['HasSizes']) {
                $sizeNameList = explode(', ', $productData['product']['Sizes']);
                foreach ( $sizeNameList as $sizeName) {
                    $sizeObj = $this->attributeOptionRepository->getOption(ucfirst(trim($sizeName)));
                    if ($sizeObj) {
                        $sizeIds[] = $sizeObj->id;
                    }
                }
            }

            if (sizeof($sizeIds) > 0) {
                $attributes['size'] = $sizeIds;
            }

            if (sizeof($colorIds) > 0) {
                $attributes['color'] = $colorIds;
            }

            $productObj = $this->productRepository->create([
                'attribute_family_id' => '1',
                'sku' => $mainProductOptionals['Sku'],
                "type" => 'configurable',
                'super_attributes' => $attributes
            ]);

            $variantsList = array_slice($productData['optionals'], 1);

            // CREATE VARIANTS
            foreach ($variantsList as $variant) {

                $tempAttributes = [];

                $productVariant = $this->productRepository->create([
                    'attribute_family_id' => '1',
                    'sku' =>  $variant['Sku'],
                    "type" => 'simple',
                    'parent_id' => $productObj->id
                ]);

                $sizeId = '';
                $colorId = '';
                $images = [];
                if (isset($variant['ColorDesc1'])) {
                    $colorObj = $this->attributeOptionRepository->getOption($variant['ColorDesc1']);
                    if ($colorObj && !in_array($colorObj->id,$tempAttributes)) {
                        $colorId = $colorObj->id;
                        $tempAttributes[] = $colorId;
                    }
                }

                if (isset($variant['Size'])) {
                    $sizeObj = $this->attributeOptionRepository->getOption((string)$variant['Size']);

                    if ($sizeObj && !in_array($sizeObj->id,$tempAttributes)) {
                        $sizeId = $sizeObj->id;
                        $tempAttributes[] = $sizeId;
                    }
                }
                
                if (isset($variant['OptionalImage1'])) {
                    $imageList = $this->productImageRepository->assignImage($variant['OptionalImage1']);
                    if ($imageList != 0) {
                        $images['files'] = $imageList['files'];
                    }
                }

                if(isset($variant['Type']) && $variant['Type'] != '') {
                    if (array_key_exists($variant['Type'], $this->categoryMapper->midocean_to_stricker_category)) {
                        $categories = $this->categoryImportService->importStrickerCategories($variant, $this->categoryMapper->midocean_to_stricker_category, $this->categoryMapper->midocean_to_stricker_subcategory);
                    }
                }

                $urlKey = strtolower($variant['Name'] . '-' . $variant['Sku']);
                $search = ['.', '\'', ' ', '"', ','];
                $replace = '-';
                $urlKey = strtolower(str_replace($search, $replace, $urlKey));

                $price = isset($variant['Price1']) ? $variant['Price1'] : 0;
                if ($variant['HasColors'] != false) {
                    $colorObj = $this->attributeOptionRepository->getOption($variant['ColorDesc1']);
                    if ($colorObj) {
                        $variants[$productVariant->id]['color'] = $colorObj->id;
                    }
                }
        
                if ($variant['HasSizes'] != false) {
                    $sizeObj = $this->attributeOptionRepository->getOption($variant['Size']);
                    if ($sizeObj) {
                        $variants[$productVariant->id]['size'] = $sizeObj->id;
                    }
                }
                $variants[$productVariant->id] = [
                    "sku" => $variant['Sku'],
                    "name" => $variant['Name'],
                    'price' => $price,
                    "weight" => $variant['Weight'] ?? 0,
                    "status" => "1",
                    "new" => "1",
                    "visible_individually" => "1",
                    "featured" => "1",
                    "guest_checkout" => "1",
                    "product_number" =>  $variant['ProdReference'] . '-' . $variant['Sku'],
                    "url_key" => $urlKey,
                    "short_description" =>(!isset($variant['ShortDescription'])) ? 'no description provided' : '<p>' . $variant['ShortDescription'] . '</p>',
                    "description" => (!isset($variant['Description'])) ? 'no description provided' : '<p>' . $variant['Description'] . '</p>',
                    "manage_stock" => "1",
                    "inventories" => [
                        1 =>  $variant['BoxQuantity'] ?? 0,
                    ],
                    'images' => $images
                ];
                
                $this->supplierRepository->create([
                    'product_id' => $productVariant->id,
                    'supplier_code' => $this->identifier
                ]);

                
                $superAttributes = [
                    '_method' => 'PUT',
                    "channel" => "default",
                    "locale" => "en",
                    'sku' => $variant['Sku'],
                    "product_number" =>  $variant['ProdReference'] . '-' . $variant['Sku'],
                    "name" =>  $variant['Name'],
                    "url_key" => $urlKey,                    
                    'price' => $price ?? '0',
                    "weight" => $variant['Weight'] ?? 0,
                    "short_description" =>(isset($variant['ShortDescription'])) ? 'no description provided' : '<p>' . $variant['ShortDescription'] . '</p>',
                    "description" => (isset($variant['Description'])) ? 'no description provided' : '<p>' . $variant['Description'] . '</p>',
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
                    "length" => $variant['BoxLengthMM'] / 10 ?? '',
                    "width" => $variant['BoxWidthMM'] / 10 ?? '',
                    "height" => $variant['BoxHeightMM'] / 10 ?? '',
                    "weight" => $variant['Weight'],
                    'categories' => $categories,// $categories,
                    'images' =>  $images,
                    'variants' => $variants
                ];

                if ($variant['HasColors'] != false) {
                    $colorObj = $this->attributeOptionRepository->getOption($variant['ColorDesc1']);
                    if ($colorObj) {
                        $superAttributes['color'] = $colorObj->id;
                    }
                }
        
                if ($variant['HasSizes'] != false) {
                    $sizeObj = $this->attributeOptionRepository->getOption($variant['Size']);
                    if ($sizeObj) {
                        $superAttributes['size'] = $sizeObj->id;
                    }
                }


                $this->productRepository->updateToShop($superAttributes, $productVariant->id, 'id');
            }

            $price = isset($mainProductOptionals['Price1']) ? $mainProductOptionals['Price1'] : 0;
            $yourPrice = isset($mainProductOptionals['YourPrice']) ? $mainProductOptionals['YourPrice'] : 0;

            $urlKey = strtolower($mainProductOptionals['Name'] . '-' . $mainProductOptionals['Sku']);
            $search = ['.', '\'', ' ', '"', ','];
            $replace = '-';
            $urlKey = strtolower(str_replace($search, $replace, $urlKey));

            $superAttributes = [
                "channel" => "default",
                "locale" => "en",
                'sku' => $mainProductOptionals['Sku'],
                "product_number" => $mainProductOptionals['ProdReference'] . '-' . $mainProductOptionals['Sku'],
                "name" => (!isset($mainProductOptionals['Name'])) ? 'no name' : $mainProductOptionals['Name'],
                "url_key" => $urlKey ?? '',
                "product_number" => $mainProductOptionals['ProdReference'],
                "short_description" =>(!isset($mainProductOptionals['ShortDescription'])) ? 'no description provided' : '<p>' . $mainProductOptionals['ShortDescription'] . '</p>',
                "description" => (!isset($mainProductOptionals['Description'])) ? 'no description provided' : '<p>' . $mainProductOptionals['Description'] . '</p>',
                "meta_title" => "",
                "meta_keywords" => "",
                "meta_description" => "",
                'price' => $price,
                'cost' => '',
                "special_price" => "",
                "special_price_from" => "",
                "special_price_to" => "",          
                "length" => $mainProductOptionals['BoxLengthMM'] / 10 ?? '',
                "width" => $mainProductOptionals['BoxWidthMM'] / 10 ?? '',
                "height" => $mainProductOptionals['BoxHeightMM'] / 10 ?? '',
                "weight" => $mainProductOptionals['Weight'],
                "new" => "1",
                "visible_individually" => "1",
                "status" => "1",
                "featured" => "1",
                "guest_checkout" => "1",
                "manage_stock" => "1",
                "inventories" => [
                    1 =>  $mainProductOptionals['BoxQuantity'] ?? 0,
                ],
                'categories' => $categories,
                'variants' => $variants,        
                'images' =>  $images
            ];

            $this->supplierRepository->create([
                'product_id' => $productObj->id,
                'supplier_code' => $this->identifier
            ]);

            $this->productRepository->updateToShop($superAttributes, $productObj->id, 'id');

    }

    public function createSimple($productData) {
            $productObj = $this->productRepository->create([
                'attribute_family_id' => '1',
                'sku' =>  $productData['optionals'][0]['Sku'],
                "type" => 'simple',
            ]);

            $sizeId = '';
            $colorId = '';
            $images = [];
            $categories = [];
            $tempAttributes = [];
            if (isset($productData['optionals'][0]['ColorDesc1']) && $productData['optionals'][0]['ColorDesc1'] != '') {
                $colorObj = $this->attributeOptionRepository->getOption($productData['optionals'][0]['ColorDesc1']);
                if ($colorObj && !in_array($colorObj->id,$tempAttributes)) {
                    $colorId = $colorObj->id;
                    $tempAttributes[] = $colorObj->id;
                }
            }

            if (isset($productData['optionals'][0]['Size']) && $productData['optionals'][0]['Size'] != '') {
                $sizeObj = $this->attributeOptionRepository->getOption($productData['optionals'][0]['Size']);
                if ($sizeObj && !in_array($sizeObj->id,$tempAttributes)) {
                    $sizeId = $sizeObj->id;
                    $tempAttributes[] = $sizeObj->id;
                }
            }
            
            if (isset($variant['OptionalImage1'])) {
                $imageList = $this->productImageRepository->assignImage($productData['optionals'][0]['OptionalImage1']);
                if ($imageList != 0) {
                    $images['files'] = $imageList['files'];
                }
            }

            $urlKey = strtolower($productData['optionals'][0]['Name'] . '-' . $productData['optionals'][0]['Sku']);
            $search = ['.', '\'', ' ', '"', ','];
            $replace = '-';
            $urlKey = strtolower(str_replace($search, $replace, $urlKey));

            $price = isset($productData['optionals'][0]['Price1']) ? $productData['optionals'][0]['Price1'] : 0;
            
            $this->supplierRepository->create([
                'product_id' => $productObj->id,
                'supplier_code' => $this->identifier
            ]);

            if(isset($productData['optionals'][0]['Type']) &&$productData['optionals'][0]['Type'] != '') {
                if (array_key_exists($productData['optionals'][0]['Type'], $this->categoryMapper->midocean_to_stricker_category)) {
                    $categories = $this->categoryImportService->importStrickerCategories($productData['optionals'][0], $this->categoryMapper->midocean_to_stricker_category, $this->categoryMapper->midocean_to_stricker_subcategory);
                }
            }

            $superAttributes = [
                '_method' => 'PUT',
                "channel" => "default",
                "locale" => "en",
                'sku' => $productObj->sku,
                "product_number" =>  $productData['optionals'][0]['ProdReference'] . '-' . $productObj->sku,
                "name" =>  $productData['optionals'][0]['Name'],
                "url_key" => $urlKey,                    
                'price' => $price ?? '0',
                "weight" => $productData['optionals'][0]['Weight'] ?? 0,
                "short_description" =>(!isset($productData['optionals'][0]['ShortDescription'])) ? 'no description provided' : '<p>' . $productData['optionals'][0]['ShortDescription'] . '</p>',
                "description" => (!isset($productData['optionals'][0]['Description'])) ? 'no description provided' : '<p>' . $productData['optionals'][0]['Description'] . '</p>',
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
                "length" =>$productData['optionals'][0]['BoxLengthMM'] / 10 ?? '',
                "width" =>$productData['optionals'][0]['BoxWidthMM'] / 10 ?? '',
                "height" => $productData['optionals'][0]['BoxHeightMM'] / 10 ?? '',
                "weight" => $productData['optionals'][0]['Weight'],
                'categories' => $categories,
                'images' =>  $images,
            ];
            if ($colorId != '') {
                $superAttributes['color'] = $colorId;
            }
    
            if ($sizeId != '') {
                $superAttributes['size'] = $sizeId;
            }

            $this->productRepository->updateToShop($superAttributes, $productObj->id, 'id');
        }
    }
