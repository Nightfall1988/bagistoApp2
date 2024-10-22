<?php

namespace App\Console\Commands\Integration;

use App\Enums\Integrations\XDConnect\CacheKey;
use App\Enums\Integrations\XDConnect\DataType;
use App\Services\Integration\XDConnect\XDConnectPrintDataMapperService;
use App\Services\Integration\XDConnect\XDConnectPrintPriceMapperService;
use App\Services\Integration\XDConnect\XDConnectProductMapperService;
use App\Services\Integration\XDConnect\XDConnectProductPriceMapperService;
use App\Services\Integration\XDConnect\XDConnectStockMapperService;
use GuzzleHttp\Client;
use SimpleXMLElement;

class XDConnectProductImport extends AbstractProductImportCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integration:xdconnect:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'XD Connects Product import';

    /**
     * HTTP Client instance
     */
    protected Client $client;

    /**
     * Create a new command instance.
     */
    private XDConnectProductMapperService $productMapperService;

    private XDConnectProductPriceMapperService $productPriceMapperService;

    private XDConnectPrintPriceMapperService $printPriceMapperService;
    private XDConnectPrintDataMapperService $printDataMapperService;
    private XDConnectStockMapperService $stockMapperService;

    public function __construct(ExtractXDConnectCategories $categoryExtractCommand,
        XDConnectProductMapperService $productMapperService,
        XDConnectProductPriceMapperService $productPriceMapperService,
        XDConnectPrintPriceMapperService $printPriceMapperService,
        XDConnectPrintDataMapperService $printDataMapperService,
        XDConnectStockMapperService $stockMapperService)
    {
        parent::__construct($categoryExtractCommand);

        $this->client = new Client([
            'headers' => [
                'Content-Type'     => 'application/json',
            ],
        ]);

        $this->productMapperService = $productMapperService;
        $this->productPriceMapperService = $productPriceMapperService;
        $this->printPriceMapperService = $printPriceMapperService;
        $this->printDataMapperService = $printDataMapperService;
        $this->stockMapperService = $stockMapperService;
    }

    /**
     * Process the content (XML).
     *
     * Parse and process the XML content fetched from the API.
     *
     * @param  string  $dataType  Type of data being processed.
     * @param  string  $content  XML content to be processed.
     */
    protected function processContent(string $dataType, string $content): void
    {
        try {
            // Parse the XML content
            $xml = new SimpleXMLElement($content);
            $data = json_decode(json_encode($xml), true);

        } catch (\Exception $e) {
            // Log an error if XML parsing fails
            $this->error("Failed to parse XML data for {$dataType}: ".$e->getMessage());

            return;
        }

        switch ($dataType) {
            case 'products':
                $this->info("Record count for {$dataType}: ".count($data['Product']));
                $this->processProductData($data['Product']);
                break;
            case 'product_prices':
                $this->info("Record count for {$dataType}: ".count($data['Product']));
                $this->processProductPriceData($data['Product']);
                break;
            case 'print_prices':
                $this->info("Record count for {$dataType}: ".count($data['SalesTechnique']));
                $this->processPrintPriceData($data['SalesTechnique']);
                break;
            case 'print_data':
                $this->info("Record count for {$dataType}: ".count($data['Product']));
                $this->processPrintData($data['Product']);
                break;
            case 'stock':
                $this->info("Record count for {$dataType}: ".count($data['Product']));
                $this->processStockData($data['Product']);
                break;
            default:
                $this->error("Unknown data type {$dataType}.");
                break;
        }

        // TODO: Process content, normalize, transform, map, etc., and store somewhere or pass directly for further processing
    }

    private function processProductData(array $data): void
    {
        $this->productMapperService->loadData($data);
        $this->productMapperService->mapProducts();
        $this->productMapperService->mapSupplierCodes();
        $this->productMapperService->mapProductFlats();
        $this->productMapperService->mapAttributeOptions();
        $this->productMapperService->mapProductCategories();
        $this->productMapperService->mapProductAttributeValues();
        $this->productMapperService->mapProductImageURLs();
    }

    private function processProductPriceData(array $data): void
    {
        $this->productPriceMapperService->loadData($data);
        $this->productPriceMapperService->mapProductAttributeValuePrices();
        $this->productPriceMapperService->mapProductAttributeValueCosts();
        $this->productPriceMapperService->mapProductFlatPrices();
        $this->productPriceMapperService->mapProductPriceIndices();
    }

    private function processPrintPriceData(array $data): void
    {
        $this->printPriceMapperService->loadData($data);
        $this->printPriceMapperService->mapPrintTechniques();
        $this->printPriceMapperService->mapPrintTechniqueVariableCosts();
    }

    private function processPrintData(array $data): void
    {
        $this->printDataMapperService->loadData($data);
        $this->printDataMapperService->mapProductPrintData();
        $this->printDataMapperService->mapPrintingPositions();
        $this->printDataMapperService->mapPositionPrintTechniques();
    }

    private function processStockData(array $data): void
    {
        $this->stockMapperService->loadData($data);
        $this->stockMapperService->mapProductInventories();
        $this->stockMapperService->mapProductInventoryIndices();
    }

    /**
     * Get a list of data types to process.
     *
     * Returns an array of data types that will be processed by the command.
     * Each data type corresponds to a specific entity or dataset.
     *
     * @return array List of data types.
     */
    protected function getDataTypes(): array
    {
        return array_map(fn ($dataType) => $dataType->value, DataType::cases());
    }

    /**
     * Get the cache key for a given data type.
     *
     * Returns a cache key string that uniquely identifies the cache entry
     * for the given data type. This key is used to store and retrieve
     * cached data.
     *
     * @param  string  $dataType  The type of data being cached.
     * @return string Cache key.
     */
    protected function getCacheKey(string $dataType): string
    {
        return CacheKey::getCacheKeyFromDataType(DataType::tryFrom($dataType))->value;
    }

    /**
     * Get the endpoint URL for a given data type.
     *
     * Returns the URL endpoint to fetch the data for the given data type
     * from the external API.
     *
     * @param  string  $dataType  The type of data being fetched.
     * @return string URL endpoint.
     */
    protected function getEndpoint(string $dataType): string
    {
        return config('integrations.xd_connect.endpoints')[$dataType];
    }

    /**
     * Get all cache keys.
     *
     * Returns an array of all cache keys used by the command.
     * Each key in the array maps to a specific cache entry.
     *
     * @return array List of cache keys.
     */
    protected function getCacheKeys(): array
    {
        return array_map(fn ($cacheKey) => $cacheKey->value, CacheKey::cases());
    }

    /**
     * Map cache key to data type.
     *
     * Converts a cache key back to its corresponding data type.
     * This is useful for determining the data type when processing cached content.
     *
     * @param  string  $cacheKey  Cache key used to store data.
     * @return string Data type.
     */
    protected function getDataTypeFromCacheKey(string $cacheKey): string
    {
        return DataType::getDataTypeFromCacheKey(CacheKey::tryFrom($cacheKey))->value;
    }

    /**
     * Get the HTTP client instance.
     *
     * @return Client HTTP client instance.
     */
    protected function getClient(): Client
    {
        return $this->client;
    }
}
