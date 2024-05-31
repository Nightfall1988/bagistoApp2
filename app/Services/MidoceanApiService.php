<?php
namespace App\Services;

use Hitexis\Product\Models\Product;
use GuzzleHttp\Client as GuzzleClient;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Product\Repositories\AttributeRepository;
use Hitexis\Product\Repositories\AttributeOptionRepository;

class MidoceanApiService {

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
        $this->url = 'https://api.midocean.com/gateway/products/2.0?language=en';
        $this->pricesUrl = 'https://api.midocean.com/gateway/pricelist/2.0/';
    }

    public function getData()
    {
        $color = '';
        $headers = [
            'Content-Type' => 'application/json',
            'x-Gateway-APIKey' => '7e18b934-577a-4fe0-bba5-e11fedfc2552',
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
    
        // Convert priceData into a more accessible format
        $priceList = [];
        foreach ($priceData['price'] as $priceItem) {
            $sku = $priceItem['sku'];
            $price = str_replace(',', '.', $priceItem['price']); // Convert price format
            $priceList[$sku] = $price;
        }

        foreach ($response as $apiProduct) {

            $product = $this->productRepository->create([
                'attribute_family_id' => '1',
                'sku' => $apiProduct->variants[0]->sku,
                "type" => "simple",
            ]);
            
            if (isset($apiProduct->variants)) {
                $variants = [];

                for ($i=1; $i<sizeof($apiProduct->variants); $i++) {

                    if (isset($apiProduct->variants[$i]->color_description)) {
                        $result = $this->attributeOptionRepository->getOption($apiProduct->variants[$i]->color_description);
                        if ($result != 0) {
                            $color = $result->id;
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
                        "color" => $color,
                        "size" => "6",
                    ];
                }
    
                $productSku = $product->sku ?? '';
                $price = isset($priceList[$productSku]) ? $priceList[$productSku] : 0;
    
                $images = [];
                // if (isset($apiProduct->variants[0]->digital_assets)) {

                //     $product->images->attach();
                // }

                $superAttributes = [
                    "channel" => "default",
                    "locale" => "en",
                    "product_number" => $apiProduct->commodity_code,
                    "name" => (!isset($apiProduct->product_name)) ? 'no name' : $apiProduct->product_name,
                    "url_key" => (!isset($apiProduct->product_name)) ? '' : strtolower($apiProduct->product_name),
                    "short_description" => (!isset($apiProduct->short_description)) ? '' : '<p>' . $apiProduct->short_description . '</p>',
                    "description" => (!isset($apiProduct->long_description)) ? '' : '<p>' . $apiProduct->long_description . '</p>',
                    "length" => $apiProduct->length,
                    "width" => $apiProduct->width,
                    "height" => $apiProduct->height,
                    "weight" => $apiProduct->net_weight,
                    "new" => "1",
                    'price' => $price,
                    "visible_individually" => "1",
                    "status" => "1",
                    "guest_checkout" => "1",
                    "manage_stock" => "1",
                    "inventories" => [
                        1 => "100"
                    ],
                    'variants' => $variants,
                    'images' => $images
                ];
                $this->productRepository->update($superAttributes, $product->id, $attribute = 'id');
            }
        }
    }
}



