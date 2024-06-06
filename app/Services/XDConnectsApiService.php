<?php
namespace App\Services;

use Hitexis\Product\Models\Product;
use GuzzleHttp\Client as GuzzleClient;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Product\Repositories\AttributeRepository;
use Hitexis\Product\Repositories\AttributeOptionRepository;

class XDConnectsApiService {

    protected $url;

    protected $pricesUrl;

    protected $productRepository;

    public function __construct(
        HitexisProductRepository $productRepository,
        AttributeRepository $attributeRepository,
        AttributeOptionRepository $attributeOptionRepository
    ) {
        $this->productRepository = $productRepository;
        $this->attributeOptionRepository = $attributeOptionRepository;
        $this->attributeRepository = $attributeRepository;
    }

    public function getData()
    {
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

        $superAttributes = [];
        $variants = [];

        foreach ($xmlProductData->Product as $product) {


            $sku = (string)$product->ItemCode;

            $variants[$sku][] = [
                'sku' => (string)$product->ModelCode,
                'name' =>  (string)$product->ItemName ?? 'no name',
                "url_key" => (!isset($product->ModelCode)) ? (string)$product->ModelCode . '_' . 
                            (string)$product->IntroDate : (string)$product->ModelCode,
                'price' =>  isset($priceDataList[(string)$product->ModelCode]->ItemPriceGross_Qty1) ? (string)$priceDataList[ (string)$product->ModelCode]->ItemPriceGross_Qty1 : 0, 
                'weight' => (string)$product->OuterCartonWeightGrossKG,
                "status" => "new",
                "color" => isset($color) ? $color : '',
                "size" =>  isset($product->Size) ? (string)$product->Size : 0,
            ];


            $productObj = $this->productRepository->create([
                'attribute_family_id' => '1',
                'sku' => $sku,
                "type" => "simple",
            ]);

            $superAttributes = [
                "channel" => "default",
                "locale" => "en",
                'sku' => $sku,
                "product_number" =>  (string)$product->ModelCode,
                "name" => (!isset($product->ItemName)) ? 'no name' : (string)$product->ItemName,
                "url_key" => (!isset($product->ItemName)) ? '' : strtolower((string)$product->ItemName),
                "short_description" => (!isset($product->ShortDescription)) ? '' : '<p>' . (string)$product->ShortDescription . '</p>',
                "description" => (!isset($product->LongDescription)) ? '' : '<p>' . (string)$product->LongDescription . '</p>',
                "meta_title" => "",
                "meta_keywords" => "",
                "meta_description" => "",
                'price' => (string)$priceDataList[(string)$product->ItemCode]->ItemPriceNet_Qty1,
                'cost' => '',
                "special_price" => "",
                "special_price_from" => "",
                "special_price_to" => "",          
                "length" => (string)$product->ItemBoxLengthCM,
                "width" => (string)$product->ItemBoxWidthCM,
                "height" => (string)$product->ItemBoxHeightCM,
                "weight" => (string)$product->ItemWeightNetGr * 1000,
                "new" => "1",
                "visible_individually" => "1",
                "status" => "1",
                "featured" => "1",
                "guest_checkout" => "1",
                "manage_stock" => "1",
                "inventories" => [
                    1 =>  (string)$product->Qty1
                ],
                'variants' => $variants,
                'brand' => (string)$product->brand,
                'categories' => [1]
            ];

            $this->productRepository->updateToShop($superAttributes, $productObj->id, 'id');
        }
    }
}



