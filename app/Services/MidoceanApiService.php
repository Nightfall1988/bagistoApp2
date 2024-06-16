<?php
namespace App\Services;

use Hitexis\Product\Models\Product;
use GuzzleHttp\Client as GuzzleClient;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Attribute\Repositories\AttributeRepository;
use Hitexis\Attribute\Repositories\AttributeOptionRepository;
use Hitexis\Product\Repositories\SupplierRepository;

class MidoceanApiService {

    protected $url;

    protected $pricesUrl;

    protected $productRepository;

    public function __construct(
        HitexisProductRepository $productRepository,
        AttributeRepository $attributeRepository,
        AttributeOptionRepository $attributeOptionRepository,
        SupplierRepository $supplierRepository
    ) {
        $this->productRepository = $productRepository;
        $this->attributeOptionRepository = $attributeOptionRepository;
        $this->attributeRepository = $attributeRepository;
        $this->supplierRepository = $supplierRepository;
        $this->url = env('MIDOECAN_PRODUCTS_URL');
        $this->pricesUrl = env('MIDOECAN_PRICES_URL');
        $this->identifier = env('MIDOECAN_IDENTIFIER');
    }

    public function getData()
    {
        $color = '';
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

            $product = $this->productRepository->create([
                'attribute_family_id' => '1',
                'sku' => $apiProduct->variants[0]->sku,
                "type" => "simple",
            ]);

            $this->supplierRepository->create([
                'product_id' => $product->id,
                'supplier_code' => $this->identifier
            ]);

            
            if (isset($apiProduct->variants)) {

                $this->setVariants($apiProduct, $product, $priceList);
            }
        }
    }


    public function setVariants($apiProduct, $product, $priceList ) {
        $variants = [];

        for ($i=1; $i<sizeof($apiProduct->variants); $i++) {

            if (isset($apiProduct->variants[$i]->color_description)) {
                $result = $this->attributeOptionRepository->getOption($apiProduct->variants[$i]->color_description);
                if ($result != 0) {
                    $color = $result->id;
                } else {
                    $color = '';
                }
            }

            $variants[$product->id] = [
                'sku' => $apiProduct->variants[$i]->sku,
                'name' => $apiProduct->product_name ?? 'no name',
                "url_key" => (!isset($apiProduct->product_name)) ? $apiProduct->variants[$i]->sku . '_' . 
                            $apiProduct->variants[$i]->variant_id : $apiProduct->product_name . '_' . 
                            $apiProduct->variants[$i]->variant_id,
                'price' =>  isset($priceList[ $apiProduct->variants[$i]->sku]) ? $priceList[ $apiProduct->variants[$i]->sku] : 0, 
                'weight' => $apiProduct->gross_weight,
                "status" => "new",
                "color" => isset($color) ? $color : '',
                "size" => "6",
            ];
        }

        $productSku = $product->sku ?? '';
        $price = isset($priceList[$productSku]) ? $priceList[$productSku] : 0;

        $replace = '-';
        $search = ['.', '\'', ' ', '"']; 
        $urlKey = isset($apiProduct->product_name) ? $apiProduct->product_name  . '-' . $apiProduct->master_id : $apiProduct->master_id; 
        $urlKey = strtolower(str_replace($search, $replace, $urlKey));        
        
        $mainProductImgs = $apiProduct->variants[0]->digital_assets;
        $images = [];
        // if (isset($mainProductImgs)) {
        //     for ($i=0; $i<count($mainProductImgs); $i++) {
        //         $images = $mainProductImgs[$i]->url_highress;
        //     }
        // }

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
            'variants' => $variants,
            'categories' => [1],
            'images' =>  ['files' => $images]
        ];

        $this->productRepository->updateToShop($superAttributes, $product->id, $attribute = 'id');
    }
}