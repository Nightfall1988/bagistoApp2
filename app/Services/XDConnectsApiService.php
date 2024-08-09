<?php
namespace App\Services;

use Hitexis\Product\Models\Product;
use GuzzleHttp\Client as GuzzleClient;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Attribute\Repositories\AttributeRepository;
use Hitexis\Attribute\Repositories\AttributeOptionRepository;
use Hitexis\Product\Repositories\SupplierRepository;
use Hitexis\Product\Repositories\ProductImageRepository;
use Symfony\Component\Console\Helper\ProgressBar;

class XDConnectsApiService {

    protected $url;

    protected $pricesUrl;

    protected $configurableProduct;

    protected $productRepository;

    protected ProgressBar $tracker;

    public function __construct(
        HitexisProductRepository $productRepository,
        AttributeRepository $attributeRepository,
        AttributeOptionRepository $attributeOptionRepository,
        SupplierRepository $supplierRepository,
        ProductImageRepository $productImageRepository,
        CategoryMapper $categoryMapper,
        CategoryImportService $categoryImportService
    ) {
        $this->productRepository = $productRepository;
        $this->attributeOptionRepository = $attributeOptionRepository;
        $this->attributeRepository = $attributeRepository;
        $this->supplierRepository = $supplierRepository;
        $this->productImageRepository = $productImageRepository;
        $this->categoryMapper = $categoryMapper;
        $this->categoryImportService = $categoryImportService;

        $this->identifier = env('XDCONNECTS_IDENTIFIER');
    }

    public function getData()
    {
        $path = 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR;

        $xmlProductData = simplexml_load_file($path . 'Xindao.V4.ProductData-en-gb-C36797.xml');
        $xmlPriceData = simplexml_load_file($path . 'Xindao.V2.ProductPrices-en-gb-C36797.xml');
        $xmlPrintData = simplexml_load_file($path . 'Xindao.V2.PrintData-en-gb-C36797.xml');

        if ($xmlProductData === false || $xmlPriceData === false || $xmlPrintData === false) {
            echo "Failed to load XML file.";
            exit;
        }

        $priceDataList = [];

        foreach ($xmlPriceData->Product as $priceElement) {
            $itemCode = (string)$priceElement->ItemCode;            
            $priceDataList[$itemCode] = $priceElement;
        }

        $productModelCodes = [];
        $groupedProducts = [];


        foreach ($xmlProductData->Product as $product) {
            $configProductModelCode = (string) $product->ModelCode;
        
            if (!array_key_exists($configProductModelCode, $groupedProducts)) {
                $groupedProducts[$configProductModelCode] = [];
            }
            $groupedProducts[$configProductModelCode][] = $product;
        }

        $this->tracker = new ProgressBar($this->output, count($groupedProducts));
        $this->tracker->start();

        if (sizeof($groupedProducts[$configProductModelCode]) == 1) {
            $this->createSimple($groupedProducts[$configProductModelCode], $priceDataList);
        } else {
            $this->createConfigurable($groupedProducts, $priceDataList);
        }

        $this->tracker->finish();
        $this->output->writeln("\nXDConnects product import finished");
        
    }

    public function createConfigurable($groupedProducts, $priceDataList) {
        foreach ($groupedProducts as $modelCode => $products) {
            $mainProduct = $groupedProducts[$modelCode][0];

            $colorList = [];
            $sizeList = [];
            $categories = [];
            $attributes = [];

            // GET ALL COLORS AND SIZES FOR PRODUCT
            foreach ($products as $product) {

                if (isset($product->Color) && (string)$product->Color != '') {
                    $result = $this->attributeOptionRepository->getOption(ucfirst((string)$product->Color));
                    if ($result != null && !in_array($result->id, $colorList)) {
                        $colorList[] = $result->id;
                    }

                    if ($result == null) {
                        {
                            $color = $this->attributeOptionRepository->create([
                                'admin_name' => ucfirst((string)$product->Color),
                                'attribute_id' => 23,
                            ]);
        
                            $colorId = $color->id;
                            $colorList[] = $colorId;
                        }
                    }
                }

                if (isset($product->Size) && (string)$product->Size != '') {
                    $result = $this->attributeOptionRepository->getOption(ucfirst((string)$product->Size));
                    if ($result != null && !in_array($result->id, $sizeList)) {
                        $sizeList[] = $result->id;
                    }

                    if ($result == null) {
                        {
                            $size = $this->attributeOptionRepository->create([
                                'admin_name' => ucfirst((string)$product->Size),
                                'attribute_id' => 24,
                            ]);
        
                            $sizeId = $size->id;
                            $sizeList[] = $sizeId;
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

            // GET ALL COLORS AND SIZES FOR PRODUCT
            $productObj = $this->productRepository->upserts([
                'channel' => 'default',
                'attribute_family_id' => '1',
                'sku' => (string)$mainProduct->ModelCode,
                "type" => 'configurable',
                'super_attributes' => $attributes
            ]);

            // VARIANT GATHERING
            foreach ($products as $variant) {

                $tempAttributes = [];
                $productVariant = $this->productRepository->upserts([
                    'channel' => 'default',
                    'attribute_family_id' => '1',
                    'sku' => (string)$variant->ItemCode,
                    "type" => 'simple',
                    'parent_id' => $productObj->id
                ]);

                $sizeId = '';
                $colorId = '';

                if (isset($variant->Color) && (string)$variant->Color != '') {
                    $colorObj = $this->attributeOptionRepository->getOption((string)$variant->Color);
                    if ($colorObj && !in_array($colorObj->id,$tempAttributes)) {
                        $colorId = $colorObj->id;
                        $tempAttributes[] = $colorId;
                    }

                    if ($colorObj == null) {
                        {
                            $color = $this->attributeOptionRepository->upserts([
                                'admin_name' => ucfirst((string)$variant->Color),
                                'attribute_id' => 23,
                            ]);
        
                            $colorId = $color->id;
                            $tempAttributes[] = $colorId;
                        }
                    }
                    
                }

                if (isset($variant->Size) && (string)$variant->Size != '') {
                    $sizeObj = $this->attributeOptionRepository->getOption((string)$variant->Size);
                    if ($sizeObj && !in_array($sizeObj->id,$tempAttributes)) {
                        $sizeId = $sizeObj->id;
                        $tempAttributes[] = $sizeId;
                    }

                    if ($sizeObj == null) {
                        {
                            $size = $this->attributeOptionRepository->upserts([
                                'admin_name' => ucfirst((string)$variant->Size),
                                'attribute_id' => 24,
                            ]);
        
                            $sizeId = $size->id;
                            $tempAttributes[] = $sizeId;
                        }
                    }
                }

                if(isset($variant->MainCategory) && (string)$variant->MainCategory != '') {
                    if (array_key_exists((string)$variant->MainCategory, $this->categoryMapper->midocean_to_xdconnects_category)) {
                        $categories = $this->categoryImportService->importXDConnectsCategories($variant, $this->categoryMapper->midocean_to_xdconnects_category, $this->categoryMapper->midocean_to_xdconnects_subcategory);
                    }
                }

                $allImagesString = (string)$variant->AllImages;

                $imageLinks = explode(', ', $allImagesString);
                
                $imageData = $this->productImageRepository->uploadImportedImagesXDConnects($imageLinks, $productVariant);
                $images['files'] = $imageData['fileList'];
                $tempPaths[] = $imageData['tempPaths'];

                $urlKey = strtolower((string)$variant->ItemName . '-' . (string)$variant->ItemCode);
                $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
                $urlKey = trim($urlKey, '-');

                $variants[$productVariant->id] = [
                    "sku" => (string)$variant->ItemCode,
                    "name" => (!isset($variant->ItemName)) ? 'no name' : (string)$variant->ItemName,
                    'price' => (string)$priceDataList[(string)$variant->ItemCode]->ItemPriceNet_Qty1 ?? '',
                    "weight" => (string)$variant->ItemWeightNetGr * 1000 ?? 0,
                    "status" => "1",
                    "new" => "1",
                    "visible_individually" => "0",
                    "status" => "1",
                    "featured" => "1",
                    "guest_checkout" => "1",
                    "product_number" =>  (string)$variant->ModelCode . '-' . (string)$variant->ItemCode,
                    "url_key" => $urlKey,
                    "short_description" => '<p>' . (string)$variant->ShortDescription . '</p>' ?? '',
                    "description" => '<p>' . (string)$variant->LongDescription . '</p>' ?? '',
                    "manage_stock" => "1",
                    "inventories" => [
                      1 => "10"
                    ],
                    'categories' => $categories,
                    'images' => $images
                ];
                
                if ($colorList != []) {
                    $variants[$productVariant->id]['color'] = $colorId;
                }

                if ($sizeList != []) {
                    $variants[$productVariant->id]['size'] = $sizeId;
                }

                $this->supplierRepository->create([
                    'product_id' => $productVariant->id,
                    'supplier_code' => $this->identifier
                ]);

                $urlKey = strtolower((string)$variant->ItemName . '-' . (string)$variant->ItemCode);
                $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
                $urlKey = trim($urlKey, '-');    
                $price = (string)$priceDataList[(string)$variant->ItemCode]->ItemPriceNet_Qty1 ?? '';

                $superAttributes = [
                    '_method' => 'PUT',
                    "channel" => "default",
                    "locale" => "en",
                    'sku' => (string)$variant->ItemCode,
                    "product_number" => (string)$variant->ModelCode . '-' . (string)$variant->ItemCode,
                    "name" => (!isset($variant->ItemName)) ? 'no name' : (string)$variant->ItemName,
                    "url_key" => $urlKey,                    
                    'price' => (string)$priceDataList[(string)$variant->ItemCode]->ItemPriceNet_Qty1 ?? '',
                    "weight" => (string)$variant->ItemWeightNetGr * 1000 ?? 0,
                    "short_description" => '<p>' . (string)$variant->ShortDescription . '</p>' ?? '',
                    "description" => '<p>' . (string)$variant->LongDescription . '</p>' ?? '',
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
                    "visible_individually" => "0",
                    "status" => "1",
                    "featured" => "1",
                    "guest_checkout" => "1",
                    "manage_stock" => "1",       
                    "length" => (string)$variant->ItemBoxLengthCM ?? '',
                    "width" => (string)$variant->ItemBoxWidthCM ?? '',
                    "height" => (string)$variant->ItemBoxHeightCM ?? '',
                    "weight" => (string)$variant->ItemWeightNetGr * 1000 ?? 0,
                    "inventories" => [
                        1 => "10"
                      ],
                    'categories' => $categories,
                    'images' =>  $images,
                ];

                if ($colorId != '') {
                    $superAttributes['color'] = $colorId;
                }
        
                if ($sizeId != '') {
                    $superAttributes['size'] = $sizeId;
                }

                $this->productRepository->updateToShop($superAttributes, $productVariant->id, 'id');
                $this->tracker->advance();
            }

            $urlKey = strtolower((string)$mainProduct->ItemName . '-' . (string)$mainProduct->ModelCode) . '-main';
            $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
            $urlKey = trim($urlKey, '-');    
            $price = (string)$priceDataList[(string)$mainProduct->ItemCode]->ItemPriceNet_Qty1 ?? '';
            
            $superAttributes = [
                '_method' => 'PUT',
                "channel" => "default",
                "locale" => "en",
                'sku' => (string)$productObj->sku,
                "product_number" => (string)$mainProduct->ModelCode . '-' . (string)$productObj->sku,
                "name" => (!isset($mainProduct->ItemName)) ? 'no name' : (string)$mainProduct->ItemName,
                "url_key" => $urlKey,                    
                'price' => (string)$priceDataList[(string)$mainProduct->ItemCode]->ItemPriceNet_Qty1 ?? '',
                "weight" => (string)$mainProduct->ItemWeightNetGr * 1000 ?? 0,
                "short_description" => '<p>' . (string)$mainProduct->ShortDescription . '</p>' ?? 'no description provided',
                "description" => '<p>' . (string)$mainProduct->LongDescription . '</p>' ?? 'no description provided',
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
                "length" => (string)$mainProduct->ItemBoxLengthCM ?? '',
                "width" => (string)$mainProduct->ItemBoxWidthCM ?? '',
                "height" => (string)$mainProduct->ItemBoxHeightCM ?? '',
                "weight" => (string)$mainProduct->ItemWeightNetGr * 1000 ?? 0,
                "inventories" => [
                    1 => "10"
                ],
                'categories' => $categories,
                'variants' => $variants,
                'images' =>  $images,
            ];

            $this->productRepository->updateToShop($superAttributes, $productObj->id, 'id');

            $this->supplierRepository->create([
                'product_id' => $productObj->id,
                'supplier_code' => $this->identifier
            ]);

            $this->tracker->advance();

        }
    }

    public function createSimple($product, $priceDataList) {

        $product = $product[0];
        $colorId= '';
        $sizeId= '';

        if (isset($product->Color) && (string)$product->Color != '') {
            $result = $this->attributeOptionRepository->getOption(ucfirst((string)$product->Color));
            if ($result != null && !in_array($result->id, $colorList)) {
                $colorId = $result->id;
                $colorList[] = $colorId;
            }

            if ($result == null) {
                {
                    $color = $this->attributeOptionRepository->create([
                        'admin_name' => ucfirst((string)$product->Color),
                        'attribute_id' => 23,
                    ]);

                    $colorId = $color->id;
                    $colorList[] = $colorId;
                }
            }
        }

        if (isset($product->Size) && (string)$product->Size != '') {
            $result = $this->attributeOptionRepository->getOption(ucfirst((string)$product->Size));
            if ($result != null && !in_array($result->id, $sizeList)) {
                $sizeId = $result->id;
                $sizeList[] = $sizeId;
            }

            if ($result == null) {
                {
                    $size = $this->attributeOptionRepository->create([
                        'admin_name' => ucfirst((string)$product->Size),
                        'attribute_id' => 24,
                    ]);

                    $sizeId = $size->id;
                    $sizeList[] = $sizeId;
                }
            }
        }

        if(isset($product->MainCategory) && (string)$product->MainCategory != '') {
            if (array_key_exists((string)$product->MainCategory, $this->categoryMapper->midocean_to_xdconnects_category)) {
                $categories = $this->categoryImportService->importXDConnectsCategories($product, $this->categoryMapper->midocean_to_xdconnects_category, $this->categoryMapper->midocean_to_xdconnects_subcategory);
            }
        }

        $productVariant = $this->productRepository->upserts([
            'channel' => 'default',
            'attribute_family_id' => '1',
            'sku' => (string)$product->ItemCode,
            "type" => 'simple',
        ]);

        $this->supplierRepository->create([
            'product_id' => $productVariant->id,
            'supplier_code' => $this->identifier
        ]);

        $allImagesString = (string)$product->AllImages;
        $imageLinks = explode(', ', $allImagesString);
        $imageData = $this->productImageRepository->uploadImportedImagesXDConnects($imageLinks, $productVariant);
        $images['files'] = $imageData['fileList'];
        $tempPaths[] = $imageData['tempPaths'];

        $urlKey = strtolower((string)$product->ItemName . '-' . (string)$product->ItemCode);
        $search = ['.', '\'', ' ', '"', ','];
        $replace = '-';
        $urlKey = strtolower(str_replace($search, $replace, $urlKey));
        $price = (string)$priceDataList[(string)$product->ItemCode]->ItemPriceNet_Qty1 ?? '';
        
        $superAttributes = [
            '_method' => 'PUT',
            "channel" => "default",
            "locale" => "en",
            'sku' => (string)$product->ItemCode,
            "product_number" => (string)$product->ModelCode . '-' . (string)$product->ItemCode,
            "name" => (!isset($product->ItemName)) ? 'no name' : (string)$product->ItemName,
            "url_key" => $urlKey,                    
            'price' => (string)$priceDataList[(string)$product->ItemCode]->ItemPriceNet_Qty1 ?? '',
            "weight" => (string)$product->ItemWeightNetGr * 1000 ?? 0,
            "short_description" => '<p>' . (string)$product->ShortDescription . '</p>' ?? '',
            "description" => '<p>' . (string)$product->LongDescription . '</p>' ?? '',
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
            "length" => (string)$product->ItemBoxLengthCM ?? '',
            "width" => (string)$product->ItemBoxWidthCM ?? '',
            "height" => (string)$product->ItemBoxHeightCM ?? '',
            "weight" => (string)$product->ItemWeightNetGr * 1000 ?? 0,
            "inventories" => [
                1 => "10"
            ],
            'categories' => $categories,
            'images' =>  $images,
        ];
        if ($colorId != '') {
            $superAttributes['color'] = $colorId;
        }

        if ($sizeId != '') {
            $superAttributes['size'] = $sizeId;
        }

        $this->productRepository->updateToShop($superAttributes, $productVariant->id, 'id');
        $this->tracker->advance();
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }
}
