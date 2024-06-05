<?php

namespace App\Services;

use Hitexis\Product\Models\Product;
use GuzzleHttp\Client as GuzzleClient;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Product\Repositories\AttributeRepository;
use Hitexis\Product\Repositories\AttributeOptionRepository;

class StrickerApiService {

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
        
        // $this->authUrl = env('STRICKER_AUTH_URL') . env('STRICKER_AUTH_TOKEN');
        // $this->url = env('STRICKER_PRODUCTS_URL');
        // $this->optionalsUrl = env('STRICKER_OPTIONALS_URL');

        // TEST DATA
        $this->url = 'https://appbagst.free.beeceptor.com';
        $this->optionalsUrl = 'https://appbagst.free.beeceptor.com/op';
    }
    
    public function getData()
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];
    
        $this->httpClient = new GuzzleClient([
            'headers' => $headers
        ]);

        // $request = $this->httpClient->get($this->authUrl);
        // $authToken = json_decode($request->getBody()->getContents())->Token;
        
        // $this->url = $this->url . $authToken . '&lang=en';
        // $this->optionalsUrl = $this->url . $authToken . '&lang=en';

        // GET PRODUCTS
        $request = $this->httpClient->get($this->url);
        $productsData = json_decode($request->getBody()->getContents(), true);
    
        // GET OPTIONALS
        $optionalsData = $this->httpClient->get($this->optionalsUrl);
        $optionalsData = json_decode($optionalsData->getBody()->getContents(), true);
    
        $products = $this->getProducts($productsData);
        $this->updateProducts($optionalsData, $products);
    }

    public function getProducts($productsData)
    {
        $products = [];

        foreach ($productsData['Products'] as $product) {
            $products[$product['ProdReference']] = $product;
        }

        return $products;
    }

    public function updateProducts($optionalsData, $products)
    {
        foreach ($optionalsData['OptionalsComplete'] as $optional) {
            
            $prodReference = $optional['ProdReference'];
            $sku = $optional['Sku'];

            if (isset($products[$prodReference])) {
                $product = $products[$prodReference];
                $product['sku'] = $sku;

                $productObj = $this->productRepository->create([
                    'attribute_family_id' => '1',
                    'sku' => $product['sku'],
                    "type" => "simple",
                ]);
            }
        
            $price = isset($optional['Price1']) ? $optional['Price1'] : 0;
            $yourPrice = isset($optional['YourPrice']) ? $optional['YourPrice'] : 0;

            $images = [];
            
            $superAttributes = [
                "channel" => "default",
                "locale" => "en",
                'sku' => $sku,
                "product_number" => $optional['ProdReference'],
                "name" => (!isset($product['Name'])) ? 'no name' : $product['Name'],
                "url_key" => (!isset($product['Name'])) ? '' : strtolower($product['Name']),
                "short_description" => (!isset($product['ShortDescription'])) ? '' : '<p>' . $product['ShortDescription'] . '</p>',
                "description" => (!isset($product['Description'])) ? '' : '<p>' . $product['Description'] . '</p>',
                "meta_title" => "",
                "meta_keywords" => "",
                "meta_description" => "",
                'price' => $price,
                'cost' => '',
                "special_price" => "",
                "special_price_from" => "",
                "special_price_to" => "",          
                "length" => $product['BoxLengthMM'] / 10,
                "width" => $product['BoxWidthMM'] / 10,
                "height" => $product['BoxHeightMM'] / 10,
                "weight" => $product['Weight'],
                "new" => "1",
                "visible_individually" => "1",
                "status" => "1",
                "featured" => "1",
                "guest_checkout" => "1",
                "manage_stock" => "1",
                "inventories" => [
                    1 =>  $product['BoxQuantity']
                ],
                'categories' => [1]
            ];

            $this->productRepository->updateToShop($superAttributes, $productObj->id, 'id');
        }
    }
}


