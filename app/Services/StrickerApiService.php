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
use Hitexis\Markup\Repositories\MarkupRepository;
use Symfony\Component\Console\Helper\ProgressBar;

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
        MarkupRepository $markupRepository,
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
        $this->markupRepository = $markupRepository;
        $this->globalMarkup = null;
        
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

        // GET PRODUCTS
        $request = $this->httpClient->get($this->url);
        $productsData = json_decode($request->getBody()->getContents(), true);

        // GET OPTIONALS
        $optionalsData = $this->httpClient->get($this->optionalsUrl);
        $optionalsData = json_decode($optionalsData->getBody()->getContents(), true);
        $this->globalMarkup = $this->markupRepository->where('markup_type', 'global')->first();

        // // TESTING DATA - RESPONSE FROM JSON FILED
        // $jsonP = file_get_contents('storage\app\private\productstest.json');
        // $productsData = json_decode($jsonP, true);
        // $jsonO = file_get_contents('storage\app\private\optionalstest.json');
        // $optionalsData = json_decode($jsonO, true);

        $products = $this->getProductsWithOptionals($productsData, $optionalsData);
        $this->updateProducts($products);
    }

    public function getProductsWithOptionals($productsData, $optionalsData)
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
        $tracker = new ProgressBar($this->output, count($products));
        $tracker->start();
        
        $productReferences = [];
        $groupedOptionals = [];
        $images = [];
        foreach ($products as $prodReference => $value) {
            if (isset($value['optionals']) && sizeof($value['optionals']) > 1) {
                $variantList = $value['optionals'];
                $attributes = $this->getConfigurableSuperAttributes($value['optionals'], $value['product']);
                $this->createConfigurable($value, $attributes);
                $tracker->advance();
            } else {
                $this->createSimple($value);
                $tracker->advance();
            }
        }

        $tracker->finish();
        $this->output->writeln("\nStricker product import finished");
    }

    public function createConfigurable($productData, $attributes) {

        $categories = [];
        $colorIds = [];
        $sizeIds = [];
        $mainProduct = $productData['product']; 
        $productVariants = [];

        $productObj = $this->productRepository->upserts([
            'channel' => 'default',
            'attribute_family_id' => '1',
            'sku' => (string)$mainProduct['ProdReference'],
            "type" => 'configurable',
            'super_attributes' => $attributes
        ]);

        // dd( $productObj, $attributes);
        foreach ($productData['optionals'] as $optional) {
            $productVariant = $this->productRepository->upserts([
                "channel" => "default",
                'attribute_family_id' => '1',
                'sku' => $optional['Sku'],
                "type" => 'simple',
                'parent_id' => $productObj->id
            ]);

            $productVariants[$productVariant->id] = $productVariant;
        } 

        // CREATE VARIANTS
        $variants = $this->getProductVariant($productData['optionals'], $productObj, $productVariants);

        $mainProductData = $productData['product'];
        $mainProductOptionals = $productData['optionals'][0];

        $cost = isset($mainProductOptionals['Price1']) ? $mainProductOptionals['Price1'] : 0;
        $yourPrice = isset($mainProductOptionals['YourPrice']) ? $mainProductOptionals['YourPrice'] : 0;

        $price = $cost + $cost * ($this->globalMarkup->percentage/100);
        $productVariant->markup()->attach($this->globalMarkup->id);
        $productObj->markup()->attach($this->globalMarkup->id);
        $urlKey = strtolower($mainProductData['Name'] . '-' . $mainProductData['ProdReference']);
        $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
        $urlKey = trim($urlKey, '-');

        $images = [];
        if (isset($mainProductOptionals['OptionalImage1'])) {
            $imageList = $this->productImageRepository->assignImage($productData['optionals'][0]['OptionalImage1']);
            if ($imageList != 0) {
                $images['files'] = $imageList['files'];
            }
        }

        // CATEGORIES
        if(isset($mainProductOptionals['Type']) && $mainProductOptionals['Type'] != '') {
            if (array_key_exists($mainProductOptionals['Type'], $this->categoryMapper->midocean_to_stricker_category)) {
                $categories = $this->categoryImportService->importStrickerCategories($mainProductOptionals, $this->categoryMapper->midocean_to_stricker_category, $this->categoryMapper->midocean_to_stricker_subcategory);
            }
        }

        $productCategory = preg_replace('/[^a-z0-9]+/i', '', strtolower($mainProductOptionals['Type'])) ?? '';
        $productSubCategory = preg_replace('/[^a-z0-9]+/i', '', strtolower($mainProductOptionals['SubType'])) ?? '';
        $material = preg_replace('/[^a-z0-9]+/i', '', strtolower($mainProductOptionals['Materials'])) ?? '';
        $brand = preg_replace('/[^a-z0-9]+/i', '', strtolower($mainProductOptionals['Brand'])) ?? '';
        $name = preg_replace('/[^a-z0-9 ]+/i', '', strtolower($mainProductOptionals['Name'])) ?? '';
        $components = preg_replace('/[^a-z0-9]+/i', '', strtolower($mainProductOptionals['ProductComponents'])) ?? '';

        $meta_title = "$material $name $components $brand";
        $meta_description = $mainProductOptionals['ShortDescription'];
        $meta_keywords = "$material, $name, $components, $brand, $productCategory, $productSubCategory";

        $superAttributes = [
            "channel" => "default",
            "locale" => "en",
            'sku' => $mainProductData['ProdReference'],
            "product_number" => $mainProductData['ProdReference'],
            "name" => (!isset($mainProductData['Name'])) ? 'no name' : $mainProductOptionals['Name'],
            "url_key" => $urlKey ?? '',
            "short_description" =>(!isset($mainProductData['ShortDescription'])) ? 'no description provided' : '<p>' . $mainProductOptionals['ShortDescription'] . '</p>',
            "description" => (!isset($mainProductData['Description'])) ? 'no description provided' : '<p>' . $mainProductOptionals['Description'] . '</p>',
            "meta_title" => $meta_title,
            "meta_keywords" => $meta_keywords,
            "meta_description" => $meta_description,
            'price' => $price,
            'cost' => $cost,
            "special_price" => "",
            "special_price_from" => "",
            "special_price_to" => "",          
            "length" => $mainProductData['BoxLengthMM'] / 10 ?? '',
            "width" => $mainProductData['BoxWidthMM'] / 10 ?? '',
            "height" => $mainProductData['BoxHeightMM'] / 10 ?? '',
            "weight" => $mainProductData['Weight'],
            "new" => "1",
            "visible_individually" => "1",
            "status" => "1",
            "featured" => "1",
            "guest_checkout" => "1",
            "manage_stock" => "1",
            "inventories" => [
                1 =>  $mainProductData['BoxQuantity'] ?? 0,
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
        
        $productObj = $this->productRepository->upserts([
            'channel' => 'default',
            'attribute_family_id' => '1',
            'sku' =>  $productData['optionals'][0]['Sku'],
            "type" => 'simple',
        ]);


        $sizeId = '';
        $colorId = '';
        $images = [];
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
        
        if (isset($productData['optionals'][0]['OptionalImage1'])) {
            $imageList = $this->productImageRepository->assignImage($productData['optionals'][0]['OptionalImage1']);
            if ($imageList != 0) {
                $images['files'] = $imageList['files'];
            }
        }

        $urlKey = strtolower($productData['optionals'][0]['Name'] . '-' . $productData['optionals'][0]['Sku']);
        $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
        $urlKey = trim($urlKey, '-');

        $cost = isset($productData['optionals'][0]['Price1']) ? $productData['optionals'][0]['Price1'] : 0;
        $yourPrice = isset($mainProductOptionals['YourPrice']) ? $mainProductOptionals['YourPrice'] : 0;

        $price = $cost + $cost * ($this->globalMarkup->percentage/100);
        $productObj->markup()->attach($this->globalMarkup->id);

        $this->supplierRepository->create([
            'product_id' => $productObj->id,
            'supplier_code' => $this->identifier
        ]);

        if(isset($productData['optionals'][0]['Type']) && $productData['optionals'][0]['Type'] != '') {
            if (array_key_exists($productData['optionals'][0]['Type'], $this->categoryMapper->midocean_to_stricker_category)) {
                $categories = $this->categoryImportService->importStrickerCategories($productData['optionals'][0], $this->categoryMapper->midocean_to_stricker_category, $this->categoryMapper->midocean_to_stricker_subcategory);
            }
        }
        $productCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($productData['optionals'][0]['Type'])) ?? '';
        $productSubCategory = preg_replace('/[^a-z0-9]+/', '', strtolower($productData['optionals'][0]['SubType'])) ?? '';
        $material = preg_replace('/[^a-z0-9]+/', '', strtolower($productData['optionals'][0]['Materials'])) ?? '';
        $brand = preg_replace('/[^a-z0-9]+/', '', strtolower($productData['optionals'][0]['Brand'])) ?? '';
        $name = preg_replace('/[^a-z0-9]+/', '', strtolower($productData['optionals'][0]['Name'])) ?? '';
        $components = preg_replace('/[^a-z0-9]+/', '', strtolower($productData['optionals'][0]['ProductComponents'])) ?? '';

        $meta_title = "$material $name $components $brand";
        $meta_description = $productData['optionals'][0]['ShortDescription'];
        $meta_keywords = "$material, $name, $components, $brand, $productCategory, $productSubCategory";

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
            "meta_title" => $meta_title,
            "meta_keywords" => $meta_keywords,
            "meta_description" => $meta_description,       
            'price' => $price,
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
            "length" =>$productData['optionals'][0]['BoxLengthMM'] / 10 ?? '',
            "width" =>$productData['optionals'][0]['BoxWidthMM'] / 10 ?? '',
            "height" => $productData['optionals'][0]['BoxHeightMM'] / 10 ?? '',
            "weight" => $productData['optionals'][0]['Weight'],
            'images' =>  $images,
            'categories' =>  $categories,
        ];
        if ($colorId != '') {
            $superAttributes['color'] = $colorId;
        }

        if ($sizeId != '') {
            $superAttributes['size'] = $sizeId;
        }
        $this->productRepository->updateToShop($superAttributes, $productObj->id, 'id');
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function getProductVariant($optionals, $mainProduct, $productVariants) {

        $tempAttributes = [];
        $categories = [];

        foreach ($productVariants as $variant) {
            $images = [];
            $variantSku = $variant->sku;
            $foundOptional = null;
        
            foreach ($optionals as $optional) {
                if ($optional['Sku'] === $variantSku) {
                    $foundOptional = $optional;
                    break;
                }
            }
        
            if ($foundOptional) {
                if (isset($foundOptional['ColorDesc1'])) {
                    $colorObj = $this->attributeOptionRepository->getOption($foundOptional['ColorDesc1']);
                    if ($colorObj && !in_array($colorObj->id,$tempAttributes)) {
                        $colorId = $colorObj->id;
                        $tempAttributes[] = $colorId;
                    }
    
                    if (!$colorObj) {
                        {
                            $colorObj = $this->attributeOptionRepository->create([
                                'admin_name' => ucfirst(trim($foundOptional['ColorDesc1'])),
                                'attribute_id' => 23,
                            ]);
        
                            $colorId = $colorObj->id;
                            $colorIds[] = $colorId;
                            $tempAttributes[] = $colorId;
                        }
                    }
                }
    
                if (isset($foundOptional['Size'])) {
                    $sizeObj = $this->attributeOptionRepository->getOption((string)$foundOptional['Size']);
    
                    if ($sizeObj && !in_array($sizeObj->id,$tempAttributes)) {
                        $sizeId = $sizeObj->id;
                        $tempAttributes[] = $sizeId;
                    }
    
                    if (!$sizeObj && !in_array($foundOptional['Size'],$tempAttributes)) {
                        {
                            $sizeObj = $this->attributeOptionRepository->create([
                                'admin_name' => ucfirst(trim($foundOptional['Size'])),
                                'attribute_id' => 24,
                            ]);
        
                            $sizeId = $sizeObj->id;
                            $sizeIds[] = $sizeId;
                            $tempAttributes[] = $sizeId;
                        }
                    }
                } elseif (sizeof(explode('-', $foundOptional['Sku'])) == 3) {
                    $sizeName = explode('-', $foundOptional['Sku'])[2];
    
                    $sizeObj = $this->attributeOptionRepository->getOption($sizeName);
    
                    if ($sizeObj && !in_array($sizeObj->id,$tempAttributes)) {
                        $sizeId = $sizeObj->id;
                        $tempAttributes[] = $sizeId;
                    }
    
                    if (!$sizeObj) {
                        $sizeObj = $this->attributeOptionRepository->create([
                            'admin_name' => $sizeName,
                            'attribute_id' => 24,
                        ]);
    
                        $sizeId = $sizeObj->id;
                        $sizeIds[] = $sizeId;
                        $tempAttributes[] = $sizeId;
                    }
                }
                
                if (isset($foundOptional['OptionalImage1'])) {
                    $imageList = $this->productImageRepository->assignImage($foundOptional['OptionalImage1']);
                    if ($imageList != 0) {
                        $images['files'] = $imageList['files'];
                    }
                }
    
                $urlKey = strtolower($foundOptional['Name'] . '-' . $foundOptional['Sku']);
                $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
                $urlKey = trim($urlKey, '-');
    
                $cost = isset($foundOptional['Price1']) ? $foundOptional['Price1'] : 0;
                $yourPrice = isset($mainProductOptionals['YourPrice']) ? $mainProductOptionals['YourPrice'] : 0;
        
                $price = $cost + $cost * ($this->globalMarkup->percentage/100);
                $variant->markup()->attach($this->globalMarkup->id);

                $variants[$variant->id] = [
                    "sku" => $foundOptional['Sku'],
                    "name" => $foundOptional['Name'],
                    'price' => $price,
                    'cost' => $cost,
                    "weight" => $foundOptional['Weight'] ?? 0,
                    "status" => "1",
                    "new" => "1",
                    "visible_individually" => "0",
                    "featured" => "1",
                    "guest_checkout" => "1",
                    "product_number" =>  $foundOptional['ProdReference'] . '-' . $foundOptional['Sku'],
                    "url_key" => $urlKey,
                    "short_description" =>(!isset($foundOptional['ShortDescription'])) ? 'no description provided' : '<p>' . $foundOptional['ShortDescription'] . '</p>',
                    "description" => (!isset($foundOptional['Description'])) ? 'no description provided' : '<p>' . $foundOptional['Description'] . '</p>',
                    "manage_stock" => "1",
                    "inventories" => [
                        1 =>  $foundOptional['BoxQuantity'] ?? 0,
                    ],
                    'images' => $images
                ];
                
                if ($foundOptional['HasSizes'] != false) {
                    $sizeObj = $this->attributeOptionRepository->getOption($foundOptional['Size']);
                    if ($sizeObj) {
                        $variants[$variant->id]['size'] = $sizeObj->id;
                    }
                }

                if ($foundOptional['HasColors'] != false) {
                    $colorObj = $this->attributeOptionRepository->getOption($foundOptional['ColorDesc1']);
                    if ($colorObj) {
                        $variants[$variant->id]['color'] = $colorObj->id;
                    }
                }
    
                $this->supplierRepository->create([
                    'product_id' => $variant->id,
                    'supplier_code' => $this->identifier
                ]);

                if(isset($foundOptional['Type']) && $foundOptional['Type']!= '') {
                    if (array_key_exists($foundOptional['Type'], $this->categoryMapper->midocean_to_stricker_category)) {
                        $categories = $this->categoryImportService->importStrickerCategories($foundOptional, $this->categoryMapper->midocean_to_stricker_category, $this->categoryMapper->midocean_to_stricker_subcategory);
                    }
                }

                $superAttributes = [
                    '_method' => 'PUT',
                    "channel" => "default",
                    "locale" => "en",
                    'sku' => $foundOptional['Sku'],
                    "product_number" =>  $foundOptional['ProdReference'] . '-' . $foundOptional['Sku'],
                    "name" =>  $foundOptional['Name'],
                    "url_key" => $urlKey,                    
                    'price' => $price ?? '0',
                    "weight" => $foundOptional['Weight'] ?? 0,
                    "short_description" =>(isset($foundOptional['ShortDescription'])) ? 'no description provided' : '<p>' . $foundOptional['ShortDescription'] . '</p>',
                    "description" => (isset($foundOptional['Description'])) ? 'no description provided' : '<p>' . $foundOptional['Description'] . '</p>',
                    "meta_title" => "",
                    "meta_keywords" => "",
                    "meta_description" => "",
                    "meta_description" => "",
                    "meta_description" => "",       
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
                    "length" => $foundOptional['BoxLengthMM'] / 10 ?? '',
                    "width" => $foundOptional['BoxWidthMM'] / 10 ?? '',
                    "height" => $foundOptional['BoxHeightMM'] / 10 ?? '',
                    "weight" => $foundOptional['Weight'],
                    'images' =>  $images,
                    'categories' =>  $categories,
                ];
    
                if ($foundOptional['HasColors'] != false) {
                    $colorObj = $this->attributeOptionRepository->getOption($foundOptional['ColorDesc1']);
                    if ($colorObj) {
                        $superAttributes['color'] = $colorObj->id;
                    }
                }
        
                if ($foundOptional['HasSizes'] != false) {
                    $sizeObj = $this->attributeOptionRepository->getOption($foundOptional['Size']);
                    if ($sizeObj) {
                        $superAttributes['size'] = $sizeObj->id;
                    }
                }
    
                $this->productRepository->updateToShop($superAttributes, $variant->id, 'id');


            } else {
                echo "No optional data found for Product Variant SKU: " . $variantSku . "\n";
            }
        }

        return $variants;
    }

    public function getConfigurableSuperAttributes($optionals, $productData) {
        $sizeIds = [];
        $colorIds = [];
        $attributes = [];
        $tempAttributes = [];
       
        // GET COLORS AND SIZES
        foreach ($optionals as $optional) {
            $skuArray = explode('-', $optional['Sku']);

            if (sizeof($skuArray) == 3) {
                $sizeName = $skuArray[2];
                $sizes = ['L', 'S', 'M', 'XS', 'XL', 'XXS', 'XXL', '3XS', '3XL', 'XXXS', 'XXXL', '4XL', '5XL'];
                if (in_array($sizeName, $sizes)) {
                    $sizeObj = $this->attributeOptionRepository->getOption($sizeName);

                    if ($sizeObj && !in_array($sizeObj->id,$tempAttributes)) {
                        $sizeId = $sizeObj->id;
                        $sizeIds[] = $sizeId;
                        $tempAttributes[] = $sizeId;
                    }

                    if (!$sizeObj &&!in_array($sizeName,$tempAttributes)) {
                        $sizeObj = $this->attributeOptionRepository->create([
                            'admin_name' => ucfirst(trim($sizeName)),
                            'attribute_id' => 24,
                        ]);

                        $sizeId = $sizeObj->id;
                        $sizeIds[] = $sizeId;
                        $tempAttributes[] = $sizeId;
                    }
                }
            }
        }

        foreach ($optionals as $optional) {
            if ($optional['HasSizes'] && isset($optional['Sizes'])) {
                $sizeNameList = explode(', ', $optional['Sizes']);
                foreach ( $sizeNameList as $sizeName) {
                    $sizeObj = $this->attributeOptionRepository->getOption(ucfirst(trim($sizeName)));
                    if ($sizeObj) {
                        $sizeIds[] = $sizeObj->id;
                    }

                    if (!$sizeObj) {
                        {
                            $sizeObj = $this->attributeOptionRepository->create([
                                'admin_name' => ucfirst(trim($sizeName)),
                                'attribute_id' => 24,
                            ]);
        
                            $sizeId = $sizeObj->id;
                            $sizeIds[] = $sizeId;
                        }
                    }
                }
            }

            if ($optional['HasColors'] && isset($optional['Colors'])) {
        
                $colorNameList = explode(', ', $optional['Colors']);
                foreach ( $colorNameList as $colorName) {
                    $colorObj = $this->attributeOptionRepository->getOption(ucfirst(trim($colorName)));
                    if ( $colorObj) {
                        $colorIds[] = $colorObj->id;
                    }

                    if (!$colorObj) {
                        {
                            $colorObj = $this->attributeOptionRepository->create([
                                'admin_name' => ucfirst(trim($colorName)),
                                'attribute_id' => 23,
                            ]);
        
                            $colorId = $colorObj->id;
                            $colorIds[] = $colorId;
                        }
                    }
                }
            } elseif ($optional['HasColors'] && !isset($optional['Colors'])) {
                if (isset($optional['ColorDesc1'])) {
                    $colorObj = $this->attributeOptionRepository->getOption(ucfirst(trim($optional['ColorDesc1'])));
                    if (!$colorObj) {
                        $colorObj = $this->attributeOptionRepository->create([
                            'admin_name' => ucfirst(trim($optional['ColorDesc1'])),
                            'attribute_id' => 23,
                        ]);                    
                    }

                    $colorId = $colorObj->id;
                    $colorIds[] = $colorId;
                }
            }
        }

        $colorIds = array_unique($colorIds);

        if (sizeof($sizeIds) > 0) {
            $attributes['size'] = $sizeIds;
        }

        if (sizeof($colorIds) > 0) {
            $attributes['color'] = $colorIds;
        }

        // if ($optionals[0]['Name'] == "THC LUANDA. Men's tubular cotton T-shirt") {
        //     dd($attributes);
        // }

        return $attributes;
    }
}
