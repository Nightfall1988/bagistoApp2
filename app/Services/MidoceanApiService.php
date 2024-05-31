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
            if (isset($apiProduct->variants)) {
                $variants = []; // Initialize variants array for each product
                foreach ($apiProduct->variants as $variant) {
                    $product = $this->productRepository->create([
                        'attribute_family_id' => '1',
                        'sku' => $variant->sku,
                        "type" => "simple",
                    ]);
    
                    if (isset($variant->color_description)) {
                        $result = $this->attributeOptionRepository->getOption($variant->color_description);
                        if ($result != 0) {
                            $color = $result->id;
                        }
                    }
    
                    $variants[$product->id] = [
                        'sku' => $variant->sku,
                        'name' => $apiProduct->product_name ?? 'no name',
                        "url_key" => (!isset($apiProduct->product_name)) ? $variant->sku . '_' . $variant->variant_id : $apiProduct->product_name . '_' . $variant->variant_id,
                        'price' => 0, 
                        'weight' => $apiProduct->gross_weight,
                        "status" => "new",
                        "color" => $color,
                        "size" => "6",
                    ];
                }
    
                // Get the product SKU and apply the price if available
                $productSku = $apiProduct->product_code ?? ''; // Assuming `product_code` is the product SKU field
                $price = isset($priceList[$productSku]) ? $priceList[$productSku] : 0;
    
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
                    'variants' => $variants
                ];
                $this->productRepository->update($superAttributes, $product->id, $attribute = 'id');
            }
        }
    }
}



