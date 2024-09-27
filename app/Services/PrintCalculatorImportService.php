<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use Hitexis\PrintCalculator\Repositories\PrintManipulationRepository;
use Hitexis\PrintCalculator\Repositories\PrintTechniqueRepository;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Symfony\Component\Console\Helper\ProgressBar;
use Illuminate\Support\Facades\DB;
use Hitexis\Product\Models\Product;

class PrintCalculatorImportService
{
    private $url;

    public $output;

    public function __construct(
        PrintTechniqueRepository $printTechniqueRepository,
        PrintManipulationRepository $printManipulationRepository,
        HitexisProductRepository $productRepository
    ) {
        $this->printTechniqueRepository = $printTechniqueRepository;
        $this->printManipulationRepository = $printManipulationRepository;
        $this->productRepository = $productRepository;
        $this->strickerUrl = env('STRICKER_PRINT_DATA');
        $this->midoceanUrl = env('MIDOECAN_PRINT_PRODUCTS_URL');
        $this->midoceanPrintUrl = env('MIDOCEAN_PRINT_DATA');
        $this->authUrl = env('STRICKER_AUTH_URL') . env('STRICKER_AUTH_TOKEN');
    }
    
    public function importPrintData()
    {
        ini_set('memory_limit', '1G');
        $this->importMidoceanPrintTechniquesAndManipulations();
        $this->importStrickerPrintData();
        // $this->importXDConnectsPrintData();

    }
    
    public function importMidoceanPrintTechniquesAndManipulations() 
    {
        ini_set('memory_limit', '1G');
        $headers = [
            'Content-Type' => 'application/json',
            'x-Gateway-APIKey' => env('MIDOECAN_API_KEY'),
        ];
    
        $this->httpClient = new GuzzleClient([
            'headers' => $headers
        ]);

        $request = $this->httpClient->get($this->midoceanUrl);
        $responseBody = $request->getBody()->getContents();
        $printData = json_decode($responseBody, true);
        $request = $this->httpClient->get($this->midoceanPrintUrl);
        $responseBody = $request->getBody()->getContents();
        $printProductData = json_decode($responseBody, true);

        $this->importMidoceanPrintManipulations($printProductData);
        $this->importMidoceanPrintData($printProductData,$printData);
    }

    public function importMidoceanPrintManipulations($data) {
        echo "Print manipulation import\n";
        $tracker = new ProgressBar($this->output, count($data['print_manipulations']));
        $tracker->start();
    
        $printManipulationsData = $data['print_manipulations'];  // Extract the print manipulations array
        $currency = $data['currency'];
        $pricelistValidFrom = $data['pricelist_valid_from'];
        $pricelistValidUntil = $data['pricelist_valid_until'];
    
        $formattedData = [];
        foreach ($printManipulationsData as $manipulation) {
            // Prepare formatted data
            $formattedData[] = [
                'currency' => $currency,
                'pricelist_valid_from' => $pricelistValidFrom,
                'pricelist_valid_until' => $pricelistValidUntil,
                'code' => $manipulation['code'],
                'description' => $manipulation['description'],
                'price' => str_replace(',', '.', $manipulation['price']),  // Convert price to a proper decimal
                'created_at' => now(),
                'updated_at' => now(),
            ];
    
            $tracker->advance();
        }
    
        // Remove existing duplicates before upserting
        foreach ($formattedData as $data) {
            DB::table('print_manipulations')
                ->where('code', $data['code'])
                ->where('description', $data['description'])
                ->delete();
        }
    
        // Perform the upsert
        DB::table('print_manipulations')->upsert(
            $formattedData, // Data to insert or update
            ['code', 'description'], // Unique constraints (columns to check for uniqueness)
            ['currency', 'pricelist_valid_from', 'pricelist_valid_until', 'price', 'updated_at'] // Columns to update
        );
    
        $tracker->finish();
    }
    

    public function importMidoceanPrintData($data,$printData) {
        ini_set('memory_limit', '1G');

        // Fetching print products data
        $response = $this->httpClient->get($this->midoceanPrintUrl);
        $data = json_decode($response->getBody(), true);
        if ($data && $printData) {
            $this->processPrintTechniques($data['print_techniques'], $printData['products']);
        }
    }

    public function processPrintTechniques($printTechniquesData, $productsData) {
        $tracker = new ProgressBar($this->output, count($productsData));
        $tracker->start();
    
        foreach ($productsData as $productData) {
            $manipulation = $this->printManipulationRepository->where('code', $productData['print_manipulation'])->first();
            $product = $this->productRepository->findWhereSimilarAttributeCode('sku', $productData['master_code']);
    
            if ($product && $product->first()) {

                foreach ($productData['printing_positions'] as $positionData) {
                    $positionId = $positionData['position_id']; // Get the position_id (e.g., "TOP COMPASS")
    
                    foreach ($positionData['printing_techniques'] as $techniqueData) {
                        // Ensure the print technique is relevant to the specific position
                        $matchingTechnique = $this->findMatchingTechnique($printTechniquesData, $techniqueData['id'], $positionData['position_id']);
    
                        // If no matching technique data is found, continue to the next one
                        if (!$matchingTechnique) {
                            continue;
                        }
    
                        try {
                            // Skip saving if var_costs or pricing_data is empty in the matching technique
                            if (empty($matchingTechnique['var_costs']) || empty($matchingTechnique['var_costs'][0]['scales'])) {
                                continue;
                            }
    
                            // Check if the print technique for this product and position already exists
                            $existingTechnique = $this->printTechniqueRepository->where([
                                ['technique_id', '=', $techniqueData['id']],
                                ['product_id', '=', $product->id],
                                ['position_id', '=', $positionId]  // Ensure the position_id is part of the condition
                            ])->first();
    
                            if ($existingTechnique) {
                                continue;
                            }
    
                            // Insert or update the print technique with the position_id
                            $printTechnique = $this->printTechniqueRepository->create([
                                'technique_id' => $techniqueData['id'],
                                'print_manipulation_id' => $manipulation->id,
                                'product_id' => $product->id,
                                'description' => $matchingTechnique['description'] ?? null,
                                'pricing_type' => $matchingTechnique['pricing_type'] ?? null,
                                'setup' => $matchingTechnique['setup'] ?? null,
                                'setup_repeat' => $matchingTechnique['setup_repeat'] ?? null,
                                'next_colour_cost_indicator' => $matchingTechnique['next_colour_cost_indicator'] ?? null,
                                'position_id' => $positionId,  // Saving the position_id
                                'range_id' => $matchingTechnique['var_costs'][0]['range_id'] ?? null,
                                'area_from' => $matchingTechnique['var_costs'][0]['area_from'] ?? null,
                                'area_to' => $matchingTechnique['var_costs'][0]['area_to'] ?? null,
                                'pricing_data' => json_encode($this->transformPricingData($matchingTechnique['var_costs'][0]['scales'])),
                                'default' => $techniqueData['default'] ?? 0,
                            ]);
    
                            // Attach print manipulation to the technique
                            $printTechnique->print_manipulation()->attack($manipulation->id);
    
                        } catch (\Exception $e) {
                            \Log::error("Failed to save print technique for product ID: " . $product->id . " - Error: " . $e->getMessage());
                        }
                    }
                }
            } else {
                \Log::warning("Product not found for master_code: " . $productData['master_code']);
            }
            $tracker->advance();
        }
    
        $tracker->finish();
    }
    
    /**
     * Find matching technique in the printTechniquesData array.
     * @param array $printTechniquesData
     * @param string $techniqueId
     * @return array|null
     */
    protected function findMatchingTechnique($printTechniquesData, $techniqueId, $positionId)
    {
        $z =[];
        foreach ($printTechniquesData as $technique) {
            if ($technique['id'] === $techniqueId && !in_array($techniqueId, $z)) {
                $z[] = $techniqueId;

                return $technique;
            }
        }
    
        return null;
    }

    /**
     * Determine if a technique is the default for a product's print position.
     *
     * @param array $techniqueData
     * @param array $productData
     * @return bool
     */
    protected function isDefaultTechnique($techniqueData, $productData) {
        foreach ($productData['printing_positions'] as $position) {
            foreach ($position['printing_techniques'] as $technique) {
                if ($technique['id'] === $techniqueData['id'] && $technique['default'] === true) {
                    return true;
                }
            }
        }

        return false;
    }

    public function importStrickerPrintData()
    {
        ini_set('memory_limit', '1G');
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $this->httpClient = new GuzzleClient([
            'headers' => $headers,
        ]);

        $request = $this->httpClient->get($this->authUrl);
        $authToken = json_decode($request->getBody()->getContents())->Token;

        $this->strickerUrl = $this->strickerUrl . $authToken . '&lang=en';
        $request = $this->httpClient->get($this->strickerUrl);

        $responseBody = $request->getBody()->getContents();
        $printData = json_decode($responseBody, true);

        $tracker = new ProgressBar($this->output, count($printData['CustomizationOptions']));
        $tracker->start();

        foreach ($printData['CustomizationOptions'] as $customization) {
            $allQuantityPricePairs = $this->getQuantityPricePairs($customization);
            if (empty($allQuantityPricePairs)) {
                continue; // Skip if no price data is available
            }

            $pricingDataJson = json_encode($allQuantityPricePairs);

            if ($pricingDataJson === false) {
                throw new \Exception('Failed to encode pricing data to JSON: ' . json_last_error_msg());
            }

            $prodReference = $customization['ProdReference'];
            $products = $this->productRepository
                ->where('sku', 'like', $prodReference . '%')
                ->get();

                foreach ($products as $product) {
                $techniqueData = [
                    'pricing_type' => '',
                    'setup' => '',
                    'setup_repeat' => '',
                    'description' => $customization['CustomizationTypeName'],
                    'next_colour_cost_indicator' => '',
                    'range_id' => '',
                    'area_from' => 0,
                    'minimum_colors' => '',
                    'area_to' => $customization['LocationMaxPrintingAreaMM'],
                    'default' => $customization['IsDefault'],
                    'pricing_data' => $pricingDataJson, // Store pricing data as JSON
                    'product_id' => $product->id,
                ];

                $this->printTechniqueRepository->upsert($techniqueData, ['prouct_id', 'pricing_data']);
            }

        }

        $tracker->finish();
    }

    public function importXDConnectsPrintData()
    {
        ini_set('memory_limit', '1G');
        $path = 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR;
        $xmlPrintData = simplexml_load_file($path . 'Xindao.V2.PrintData-en-gb-C36797.xml');
        $xmlPriceData = simplexml_load_file($path . 'Xindao.V2.ProductPrices-en-gb-C36797.xml');

        $productPrices = [];

        foreach ($xmlPriceData->Product as $product) {
            $productPrices[(string) $product->ModelCode] = [
                'Qty1' => (string) $product->Qty1,
                'Qty2' => (string) $product->Qty2,
                'Qty3' => (string) $product->Qty3,
                'Qty4' => (string) $product->Qty4,
                'Qty5' => (string) $product->Qty5,
                'Qty6' => (string) $product->Qty6,
                'ItemPriceNet_Qty1' => (string) $product->ItemPriceNet_Qty1,
                'ItemPriceNet_Qty2' => (string) $product->ItemPriceNet_Qty2,
                'ItemPriceNet_Qty3' => (string) $product->ItemPriceNet_Qty3,
                'ItemPriceNet_Qty4' => (string) $product->ItemPriceNet_Qty4,
                'ItemPriceNet_Qty5' => (string) $product->ItemPriceNet_Qty5,
                'ItemPriceNet_Qty6' => (string) $product->ItemPriceNet_Qty6,
            ];
        }

        $prodReferences = array_keys($productPrices);
        $products = $this->productRepository
            ->whereIn('sku', $prodReferences)
            ->get()
            ->keyBy('sku');

        $tracker = new ProgressBar($this->output, count($xmlPrintData->Product));
        $tracker->start();

        foreach ($xmlPrintData->Product as $printProduct) {
            $prodReference = (string) $printProduct->ModelCode;
            $product = $products->get($prodReference);

            if ($product) {
                $allQuantityPricePairs = $this->getQuantityPricePairsXDConnects($productPrices[$prodReference]);

                if (empty($allQuantityPricePairs)) {
                    continue; // Skip if no price data is available
                }

                $pricingDataJson = json_encode($allQuantityPricePairs);
                if ($pricingDataJson === false) {
                    throw new \Exception('Failed to encode pricing data to JSON: ' . json_last_error_msg());
                }

                $techniqueData = [
                    'pricing_type' => '',
                    'setup' => '',
                    'setup_repeat' => '',
                    'description' => (string) $printProduct->PrintTechnique,
                    'next_colour_cost_indicator' => '',
                    'range_id' => '',
                    'area_from' => (string) $printProduct->AreaFrom ?? 0,
                    'minimum_colors' => '',
                    'area_to' => (string) $printProduct->MaxPrintArea ?? 0,
                    'default' => (string) $printProduct->Default,
                    'pricing_data' => $pricingDataJson, // Store pricing data as JSON
                    'product_id' => $product->id,
                ];

                $this->printTechniqueRepository->create($techniqueData);
            }

            $tracker->advance();
        }

        $tracker->finish();
    }

    public function getQuantityPricePairs($customization)
    {
        $resultArray = [];

        $i = 1;
        while (isset($customization["MinQt{$i}"]) && isset($customization["Price{$i}"])) {
            if ($customization["MinQt{$i}"] !== null && $customization["Price{$i}"] !== null) {
                $resultArray[] = [
                    'MinQt' => $customization["MinQt{$i}"],
                    'Price' => $customization["Price{$i}"],
                ];
            }
            $i++;
        }

        return $resultArray;
    }

    public function getQuantityPricePairsXDConnects($productPrices)
    {
        $resultArray = [];

        $i = 1;
        while (isset($productPrices["Qty{$i}"]) && isset($productPrices["ItemPriceNet_Qty{$i}"])) {
            if ($productPrices["Qty{$i}"] !== null && $productPrices["ItemPriceNet_Qty{$i}"] !== null) {
                $resultArray[] = [
                    'MinQt' => $productPrices["Qty{$i}"],
                    'Price' => $productPrices["ItemPriceNet_Qty{$i}"],
                ];
            }
            $i++;
        }

        return $resultArray;
    }

    protected function transformPricingData(array $scales)
    {
        return array_map(function ($scale) {
            return [
                'MinQt' => $scale['minimum_quantity'] ?? 0,  // Default to 0 if 'minimum_quantity' is not set
                'Price' => floatval(str_replace(',', '.', $scale['price'] ?? 0))  // Converting string to float, assuming comma as decimal separator
            ];
        }, $scales);
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }
}


