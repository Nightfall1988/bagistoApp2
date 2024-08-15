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
        // dd($response->print_techniques);
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

    public function importXDConnectsPrintData() {
        $xmlPrintData = simplexml_load_file($path . 'Xindao.V2.PrintData-en-gb-C36797.xml');

        foreach ($xmlPriceData->Product as $product) {
            $productPrices[(string)$product->ItemCode] = [
                'FeedCreatedDateTime' => (string)$product->FeedCreatedDateTime,
                'ItemPriceLastModifiedDateTime' => (string)$product->ItemPriceLastModifiedDateTime,
                'Currency' => (string)$product->Currency,
                'ModelCode' => (string)$product->ModelCode,
                'ItemCode' => (string)$product->ItemCode,
                'ItemName' => (string)$product->ItemName,
                'NonStandardDiscount' => (string)$product->NonStandardDiscount,
                'Outlet' => (string)$product->Outlet,
                'AllPrintCodes' => (string)$product->AllPrintCodes,
                'MOQBlankOrder' => (string)$product->MOQBlankOrder,
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
                'ItemPriceGross_Qty1' => (string)$product->ItemPriceGross_Qty1,
                'ItemPriceGross_Qty2' => (string)$product->ItemPriceGross_Qty2,
                'ItemPriceGross_Qty3' => (string)$product->ItemPriceGross_Qty3,
                'ItemPriceGross_Qty4' => (string)$product->ItemPriceGross_Qty4,
                'ItemPriceGross_Qty5' => (string)$product->ItemPriceGross_Qty5,
                'ItemPriceGross_Qty6' => (string)$product->ItemPriceGross_Qty6,
                'AdditionalCostDesc' => (string)$product->AdditionalCostDesc,
                'AdditionalCost' => (string)$product->AdditionalCost,
            ];
        }

        foreach ($xmlPrintData->Product as $product) {
            $productsPrintData[] = [
                'FeedCreatedDateTime' => (string)$product->FeedCreatedDateTime,
                'PrintDataLastModifiedDateTime' => (string)$product->PrintDataLastModifiedDateTime,
                'ModelCode' => (string)$product->ModelCode,
                'ItemCode' => (string)$product->ItemCode,
                'ItemName' => (string)$product->ItemName,
                'PrintCode' => (string)$product->PrintCode,
                'PrintTechnique' => (string)$product->PrintTechnique,
                'Default' => (string)$product->Default,
                'PrintPosition' => (string)$product->PrintPosition,
                'PrintPositionCode' => (string)$product->PrintPositionCode,
                'MaxPrintWidthMM' => (string)$product->MaxPrintWidthMM,
                'MaxPrintHeightMM' => (string)$product->MaxPrintHeightMM,
                'MaxPrintArea' => (string)$product->MaxPrintArea,
                'MaxColors' => (string)$product->MaxColors,
                'FullColor' => (string)$product->FullColor,
                'VariableDataPrinting' => (string)$product->VariableDataPrinting,
                'LineDrawing' => (string)$product->LineDrawing,
                'ArtworkFile' => (string)$product->ArtworkFile,
                'DeliveryCountry' => (string)$product->DeliveryCountry,
                'DeliveryTimePrintOrder' => (string)$product->DeliveryTimePrintOrder,
                'Qty1' => $productPrices[(string)$product->ItemCode]['Qty1'],
                'ItemPriceNet_Qty1' => $productPrices[(string)$product->ItemCode]['ItemPriceNet_Qty1'],
                'ItemPriceGross_Qty1' => $productPrices[(string)$product->ItemCode]['ItemPriceGross_Qty1'],
            ];
        }

        foreach ($productsPrintData as $product)  {
            $technique = $this->printTechniqueRepository->create(
                [
                    'pricing_type' => '',
                    'setup' => '',
                    'setup_repeat' => '',
                    'description' => $productsPrintData['PrintTechnique'],
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

    public function getQuantityPricePairsXDConnects($customization) {
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

    public function setOutput($output)
    {
        $this->output = $output;
    }
}