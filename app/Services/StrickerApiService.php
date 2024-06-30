<?php

namespace App\Services;

use Hitexis\Product\Models\Product;
use GuzzleHttp\Client as GuzzleClient;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Attribute\Repositories\AttributeRepository;
use Hitexis\Attribute\Repositories\AttributeOptionRepository;
use Hitexis\Product\Repositories\SupplierRepository;
use Hitexis\Product\Repositories\ProductImageRepository;

class StrickerApiService {

    protected $url;

    protected $pricesUrl;

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
        $this->authUrl = env('STRICKER_AUTH_URL') . env('STRICKER_AUTH_TOKEN');
        $this->url = env('STRICKER_PRODUCTS_URL');
        $this->optionalsUrl = env('STRICKER_OPTIONALS_URL');
        $this->identifier = env('STRICKER_IDENTIFIER');

        // dd( $this->url, $this->authUrl, $this->optionalsUrl);
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

        // TEST RESPONSE FROM JSON FILE
        // $json = file_get_contents('storage\app\private\New_Reques-complete.json');
        // $optionalsData = json_decode($json, true);
        // dd( $optionalsData );

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
        $images = [];
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


                $imageName = str_replace('-', '_', $productObj->sku);

                if (isset($optional['OptionalImage1'])) {
                    $imageList = $this->productImageRepository->assignImage($optional['OptionalImage1']);
                    if ($imageList != 0) {
                        $images['files'] = $imageList['files'];
                    }
                }

                $this->supplierRepository->create([
                    'product_id' => $productObj->id,
                    'supplier_code' => $this->identifier
                ]);
            }
        
            $price = isset($optional['Price1']) ? $optional['Price1'] : 0;
            $yourPrice = isset($optional['YourPrice']) ? $optional['YourPrice'] : 0;

            $search = ['.', '\'', ' ', '"', ','];
            $replace = '-';
            $name = $product['Name'];
            $urlKey = strtolower(str_replace($search, $replace, $name));

            $superAttributes = [
                "channel" => "default",
                "locale" => "en",
                'sku' => $sku,
                "product_number" => $optional['ProdReference'],
                "name" => (!isset($product['Name'])) ? 'no name' : $product['Name'],
                "url_key" => (!isset($product['Name'])) ? $sku : $urlKey,
                "short_description" =>(!isset($product['ShortDescription'])) ? 'no description provided' : '<p>' . $product['ShortDescription'] . '</p>',
                "description" => (!isset($product['Description'])) ? 'no description provided' : '<p>' . $product['Description'] . '</p>',
                "meta_title" => "",
                "meta_keywords" => "",
                "meta_description" => "",
                'price' => $price,
                'cost' => '',
                "special_price" => "",
                "special_price_from" => "",
                "special_price_to" => "",          
                "length" => $product['BoxLengthMM'] / 10 ?? '',
                "width" => $product['BoxWidthMM'] / 10 ?? '',
                "height" => $product['BoxHeightMM'] / 10 ?? '',
                "weight" => $product['Weight'],
                "new" => "1",
                "visible_individually" => "1",
                "status" => "1",
                "featured" => "1",
                "guest_checkout" => "1",
                "manage_stock" => "1",
                "inventories" => [
                    1 =>  $product['BoxQuantity'] ?? 0,
                ],
                'categories' => [1],
                'images' => $images
            ];

            $this->productRepository->updateToShop($superAttributes, $productObj->id, 'id');
        }
    }
}


