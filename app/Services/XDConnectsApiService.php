<?php
namespace App\Services;

use Hitexis\Product\Models\Product;
use GuzzleHttp\Client as GuzzleClient;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Attribute\Repositories\AttributeRepository;
use Hitexis\Attribute\Repositories\AttributeOptionRepository;
use Hitexis\Product\Repositories\SupplierRepository;
use Hitexis\Product\Repositories\ProductImageRepository;

class XDConnectsApiService {

    protected $url;

    protected $pricesUrl;

    protected $configurableProduct;

    protected $productRepository;

    public function __construct(
        HitexisProductRepository $productRepository,
        AttributeRepository $attributeRepository,
        AttributeOptionRepository $attributeOptionRepository,
        SupplierRepository $supplierRepository,
        ProductImageRepository $productImageRepository
    ) {
        $this->productRepository = $productRepository;
        $this->attributeOptionRepository = $attributeOptionRepository;
        $this->attributeRepository = $attributeRepository;
        $this->supplierRepository = $supplierRepository;
        $this->productImageRepository = $productImageRepository;
        $this->identifier = env('XDCONNECTS_IDENTIFIER');
    }

    public function getData()
    {
        ini_set('memory_limit', '512M');

        $xmlProductData = simplexml_load_file('storage\app\private\Xindao.V4.ProductData-en-gb-C36797.xml');
        $xmlPriceData = simplexml_load_file('storage\app\private\Xindao.V2.ProductPrices-en-gb-C36797.xml');
        $xmlPrintData = simplexml_load_file('storage\app\private\Xindao.V2.PrintData-en-gb-C36797.xml');

        if ($xmlProductData === false || $xmlPriceData === false || $xmlProductData === false) {
            echo "Failed to load XML file.";
            exit;
        }

        $priceDataList = [];

        foreach ($xmlPriceData->Product as $priceElement) {
            $itemCode = (string)$priceElement->ItemCode;            
            $priceDataList[ $itemCode] = $priceElement;
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

        $this->createConfigurable($groupedProducts, $priceDataList);
    }

    public function createConfigurable($groupedProducts, $priceDataList) {



        foreach ($groupedProducts as $modelCode => $products) {

            if (sizeof($groupedProducts) == 1) {
                $this->createSimple($groupedProducts[$modelCode]);
            }

            $colorList = [];
            $sizeList = [];

            $mainProduct = $products[0];

            foreach ($products as $product) {


                if (isset($product->Color)) {
                    $result = $this->attributeOptionRepository->getOption(ucfirst((string)$product->Color));
                    if ($result != null && !in_array($result->id, $colorList)) {
                        $colorList[] = $result->id;
                    }
                }

                if (isset($product->Size)) {
                    $result = $this->attributeOptionRepository->getOption(ucfirst((string)$product->Size));
                    if ($result != null && !in_array($result->id, $sizeList)) {
                        $sizeList[] = $result->id;
                    }
                }
            }

            $attributes = [];
            if (sizeof($sizeList) > 0) {
                $attributes['size'] = $sizeList;
            }

            if (sizeof($colorList) > 0) {
                $attributes['color'] = $colorList;
            }

            $productObj = $this->productRepository->create([
                'attribute_family_id' => '1',
                'sku' => (string)$mainProduct->ItemCode,
                "type" => 'configurable',
                'super_attributes' => $attributes
            ]);

            // VARIANT GATHERING
            $variantsList = array_slice($products, 1);

            foreach ($variantsList as $variant) {

                $tempAttributes = [];

                $productVariant = $this->productRepository->create([
                    'attribute_family_id' => '1',
                    'sku' => (string)$variant->ItemCode,
                    "type" => 'simple',
                    'parent_id' => $productObj->id
                ]);

                $sizeId = '';
                $colorId = '';

                if (isset($variant->Color)) {
                    $colorObj = $this->attributeOptionRepository->getOption((string)$variant->Color);
                    if ($colorObj && !in_array($colorObj->id,$tempAttributes)) {
                        $colorId = $colorObj->id;
                        $tempAttributes[] = $colorId;
                    }
                }

                if (isset($variant->Size)) {
                    $sizeObj = $this->attributeOptionRepository->getOption((string)$variant->Size);
                    if ($sizeObj && !in_array($sizeObj->id,$tempAttributes)) {
                        $sizeId = $sizeObj->id;
                        $tempAttributes[] = $sizeId;
                    }
                }

                $allImagesString = (string)$variant->AllImages; // Convert the SimpleXMLElement to a string

                $imageLinks = explode(', ', $allImagesString);
                
                $imageData = $this->productImageRepository->uploadImportedImagesXDConnects($imageLinks, $productVariant);
                $images['files'] = $imageData['fileList'];
                $tempPaths[] = $imageData['tempPaths'];

                $urlKey = strtolower((string)$variant->ItemName . '-' . (string)$variant->ItemCode);
                $search = ['.', '\'', ' ', '"', ','];
                $replace = '-';
                $urlKey = strtolower(str_replace($search, $replace, $urlKey));

                
                $variants[$productVariant->id] = [
                    "sku" => (string)$variant->ItemCode,
                    "name" => (!isset($variant->ItemName)) ? 'no name' : (string)$variant->ItemName,
                    'price' => (string)$priceDataList[(string)$variant->ItemCode]->ItemPriceNet_Qty1 ?? '',
                    "weight" => (string)$variant->ItemWeightNetGr * 1000 ?? 0,
                    "status" => "1",
                    "new" => "1",
                    "visible_individually" => "1",
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

                $urlKey = strtolower((string)$mainProduct->ItemName . '-' . (string)$mainProduct->ItemCode);
                $search = ['.', '\'', ' ', '"', ','];
                $replace = '-';
                $urlKey = strtolower(str_replace($search, $replace, $urlKey));
                $price = (string)$priceDataList[(string)$product->ItemCode]->ItemPriceNet_Qty1 ?? '';
                
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
                    "visible_individually" => "1",
                    "status" => "1",
                    "featured" => "1",
                    "guest_checkout" => "1",
                    "manage_stock" => "1",       
                    "length" => (string)$product->ItemBoxLengthCM ?? '',
                    "width" => (string)$product->ItemBoxWidthCM ?? '',
                    "height" => (string)$product->ItemBoxHeightCM ?? '',
                    "weight" => (string)$product->ItemWeightNetGr * 1000 ?? 0,
                    // 'categories' => [2],// $categories,
                    'images' =>  $images,
                ];
                if ($colorId != '') {
                    $superAttributes['color'] = $colorId;
                }
        
                if ($sizeId != '') {
                    $superAttributes['size'] = $sizeId;
                }

                $this->productRepository->updateToShop($superAttributes, $productVariant->id, 'id');
            }

            $superAttributes = [
                "channel" => "default",
                "locale" => "en",
                'sku' => $productObj->sku,
                "product_number" =>  (string)$mainProduct->ModelCode,
                "name" => (!isset($mainProduct->ItemName)) ? 'no name' : (string)$mainProduct->ItemName,
                "url_key" => $urlKey ?? '',
                "short_description" => '<p>' . (string)$mainProduct->ShortDescription . '</p>' ?? (string)$mainProduct->ItemName,
                "description" => '<p>' . (string)$mainProduct->LongDescription . '</p>' ?? '',
                "meta_title" => "",
                "meta_keywords" => "",
                "meta_description" => "",
                'price' => (string)$priceDataList[(string)$mainProduct->ItemCode]->ItemPriceNet_Qty1 ?? '',
                'cost' => '',
                "special_price" => "",
                "special_price_from" => "",
                "special_price_to" => "",          
                "length" => (string)$mainProduct->ItemBoxLengthCM ?? '',
                "width" => (string)$mainProduct->ItemBoxWidthCM ?? '',
                "height" => (string)$mainProduct->ItemBoxHeightCM ?? '',
                "weight" => (string)$mainProduct->ItemWeightNetGr * 1000 ?? 0,
                "new" => "1",
                "visible_individually" => "1",
                "status" => "1",
                "featured" => "1",
                "guest_checkout" => "1",
                "manage_stock" => "1",
                "inventories" => [
                    1 =>  (string)$mainProduct->Qty1
                ],
                'variants' => $variants,
                'brand' => (string)$mainProduct->brand,
                // 'categories' => [2],//$categories,
                'images' =>  $images
            ];

            $this->supplierRepository->create([
                'product_id' => $productObj->id,
                'supplier_code' => $this->identifier
            ]);

            $this->productRepository->updateToShop($superAttributes, $productObj->id, 'id');
        }
    }

    public function createSimple($product) {
        dd($product);
    }
}



