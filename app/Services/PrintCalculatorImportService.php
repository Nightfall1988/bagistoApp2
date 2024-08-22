<?php
namespace App\Services;
use GuzzleHttp\Client as GuzzleClient;
use Hitexis\PrintCalculator\Repositories\PrintManipulationRepository;
use Hitexis\PrintCalculator\Repositories\PrintTechniqueRepository;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Symfony\Component\Console\Helper\ProgressBar;

class PrintCalculatorImportService {

    private $url;
    
    public $output;

    public function __construct(
        PrintTechniqueRepository $printTechniqueRepository,
        PrintManipulationRepository $printManipulationRepository,
        HitexisProductRepository $productRepository,
    ) {
        $this->printTechniqueRepository = $printTechniqueRepository;
        $this->printManipulationRepository = $printManipulationRepository;
        $this->productRepository = $productRepository;
        $this->url = env('STRICKER_PRINT_DATA');
        $this->authUrl = env('STRICKER_AUTH_URL') . env('STRICKER_AUTH_TOKEN');

    }

    public function importPrintData() {
        ini_set('memory_limit', '1G');

        $this->importStrickerPrintData();
        $this->importXDConnectsPrintData();
    }

    public function importMidoceanPrintData() {
        // $headers = [
        //     'Content-Type' => 'application/json',
        //     'x-Gateway-APIKey' => env('MIDOECAN_API_KEY'),
        // ];
    
        // $this->httpClient = new GuzzleClient([
        //     'headers' => $headers
        // ]);

        // // GET TECHNIQUES
        // $request = $this->httpClient->get($this->url);
        // $response = json_decode($request->getBody()->getContents());

        // foreach ($response->print_manipulations as $manipulation) {
        //     # code...
        // }
    }
    
    public function importStrickerPrintData() {
        ini_set('memory_limit', '1G');
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $this->httpClient = new GuzzleClient([
            'headers' => $headers
        ]);

        $request = $this->httpClient->get($this->authUrl);
        $authToken = json_decode($request->getBody()->getContents())->Token;

        $this->url = $this->url . $authToken . '&lang=en';
        $headers = [
            'Content-Type' => 'application/json',
        ];
    
        $this->httpClient = new GuzzleClient([
            'headers' => $headers
        ]);
        $request = $this->httpClient->get($this->url);

        $responseBody = $request->getBody()->getContents();
        
        $printData = json_decode($responseBody, true);

        $currency = $printData['Currency'];
        $language = $printData['Language'];
        $productRefs = [];
        $products = [];
        $tracker = new ProgressBar($this->output, count($printData['CustomizationOptions']));
        $tracker->start();
        foreach ($printData['CustomizationOptions'] as $key => $customization) {

            $allQuantityPricePairs = $this->getQuantityPricePairs($customization);

            $prodReference = $customization['ProdReference'];

                $products = $this->productRepository
                    ->where('sku', 'like', $prodReference. '%')
                    ->get();

            foreach ($products as $product) {

                foreach ($allQuantityPricePairs as $pair) {

                    $technique = $this->printTechniqueRepository->create(
                        [
                            'pricing_type' => '',
                            'setup' => '',
                            'setup_repeat' => '',
                            'description' => $customization['CustomizationTypeName'],
                            'next_colour_cost_indicator' => '',
                            'range_id' => '',
                            'area_from' => 0,
                            'minimum_colors' => '',
                            'area_to' => $customization['LocationMaxPrintingAreaMM'],
                            'next_price' => '',
                            'default' => $customization['IsDefault'],
                            'minimum_quantity' => $pair['MinQt'],
                            'price' => $pair['Price'],
                            'product_id' => $product->id
                        ]
                    );
                }

                $tracker->advance();

            }

            $productRefs[] = $prodReference;
        }
        $productRefs = [];

        $tracker->finish();
    }

    public function importXDConnectsPrintData()
    {
        ini_set('memory_limit', '1G');
        $path = 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR;
        $xmlPrintData = simplexml_load_file($path . 'Xindao.V2.PrintData-en-gb-C36797.xml');
        $xmlPriceData = simplexml_load_file($path . 'Xindao.V2.ProductPrices-en-gb-C36797.xml');

        // Convert XML price data to associative array
        $productPrices = [];

        foreach ($xmlPriceData->Product as $product) {
            $productPrices[(string)$product->ModelCode] = [
                'Qty1' => (string)$product->Qty1,
                'Qty2' => (string)$product->Qty2,
                'Qty3' => (string)$product->Qty3,
                'Qty4' => (string)$product->Qty4,
                'Qty5' => (string)$product->Qty5,
                'Qty6' => (string)$product->Qty6,
                'ItemPriceNet_Qty1' => (string)$product->ItemPriceNet_Qty1,
                'ItemPriceNet_Qty2' => (string)$product->ItemPriceNet_Qty2,
                'ItemPriceNet_Qty3' => (string)$product->ItemPriceNet_Qty3,
                'ItemPriceNet_Qty4' => (string)$product->ItemPriceNet_Qty4,
                'ItemPriceNet_Qty5' => (string)$product->ItemPriceNet_Qty5,
                'ItemPriceNet_Qty6' => (string)$product->ItemPriceNet_Qty6,
                // Add other quantities and prices if needed
            ];    
        }


        // Extract quantity-price pairs
        $printQtyPricePairs = $this->getQuantityPricePairsXDConnects($productPrices);

        // Retrieve all products in one batch to minimize queries
        $prodReferences = array_keys($productPrices);
        $products = $this->productRepository
            ->whereIn('sku', $prodReferences)
            ->get()
            ->keyBy('sku');
    
        $tracker = new ProgressBar($this->output, count($xmlPrintData->Product));
        $tracker->start();
    
        foreach ($xmlPrintData->Product as $printProduct) {
            $prodReference = (string)$printProduct->ModelCode;
            $product = $products->get($prodReference);
    
            if ($product) {
                foreach ($printQtyPricePairs as $pair) {
                    $technique = $this->printTechniqueRepository->create([
                        'pricing_type' => '',
                        'setup' => '',
                        'setup_repeat' => '',
                        'description' => (string)$printProduct->PrintTechnique,
                        'next_colour_cost_indicator' => '',
                        'range_id' => '',
                        'area_from' => (string)$printProduct->AreaFrom ?? 0, // Use actual value if available
                        'minimum_colors' => '',
                        'area_to' => (string)$printProduct->MaxPrintArea ?? 0,
                        'next_price' => '',
                        'default' => (string)$printProduct->Default,
                        'minimum_quantity' => $pair['MinQt'],
                        'price' => $pair['Price'],
                        'product_id' => $product->id
                    ]);
                }
                $tracker->advance();
            }
        }
    
        $tracker->finish();
    }

    public function getQuantityPricePairs($customization) {
        $resultArray = [];

        // Loop through the array to group MinQt and Price pairs
        $i = 1;
        while (isset($customization["MinQt{$i}"]) && isset($customization["Price{$i}"])) {
            if ($customization["MinQt{$i}"] !== null && $customization["Price{$i}"] !== null) {
                $resultArray[] = [
                    'MinQt' => $customization["MinQt{$i}"],
                    'Price' => $customization["Price{$i}"]
                ];
            }
            $i++;
        }

        return $resultArray;
    }

    public function getQuantityPricePairsXDConnects($productPrices)
    {
        $resultArray = [];
    
        foreach ($productPrices as $itemCode => $product) {
            $i = 1;
            while (isset($product["Qty{$i}"]) && isset($product["ItemPriceNet_Qty{$i}"])) {
                if ($product["Qty{$i}"] !== null && $product["ItemPriceNet_Qty{$i}"] !== null) {
                    $resultArray[] = [
                        'MinQt' => $product["Qty{$i}"],
                        'Price' => $product["ItemPriceNet_Qty{$i}"]
                    ];
                }
                $i++;
            }
        }

        return $resultArray;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }
}