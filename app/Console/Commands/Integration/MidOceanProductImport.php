<?php

namespace App\Console\Commands\Integration;

use App\Enums\Integrations\Midocean\CacheKey;
use App\Enums\Integrations\Midocean\DataType;
use App\Repositories\Integration\ProductImportRepository;
use App\Services\Integration\MidOcean\MidOceanPrintDataMapperService;
use App\Services\Integration\MidOcean\MidOceanPrintPriceMapperService;
use App\Services\Integration\MidOcean\MidOceanProductMapperService;
use App\Services\Integration\MidOcean\MidOceanProductPriceMapperService;
use App\Services\Integration\MidOcean\MidOceanStockMapperService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\RequestInterface;

class MidOceanProductImport extends AbstractProductImportCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integration:midocean:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'MidOcean Product import';

    protected MidOceanProductMapperService $productMapperService;

    protected MidOceanProductPriceMapperService $productPriceMapperService;

    protected MidOceanPrintPriceMapperService $printPriceMapperService;

    protected MidOceanPrintDataMapperService $printDataMapperService;

    protected MidOceanStockMapperService $stockMapperService;

    protected ProductImportRepository $productImportRepository;

    /**
     * HTTP Client instance
     */
    protected Client $client;

    /**
     * Create a new command instance.
     */
    public function __construct(ExtractMidoceanCategories $categoryExtractCommand,
        MidOceanProductMapperService $productMapperService,
        ProductImportRepository $productImportRepository,
        MidOceanProductPriceMapperService $productPriceMapperService,
        MidOceanPrintPriceMapperService $printPriceMapperService,
        MidOceanPrintDataMapperService $printDataMapperService,
        MidOceanStockMapperService $stockMapperService)
    {
        parent::__construct($categoryExtractCommand);
        $this->client = new Client([
            'headers' => [
                'Content-Type'     => 'application/json',
                'x-Gateway-APIKey' => config('integrations.midocean.auth.api-key'),
            ],
        ]);
        $this->productMapperService = $productMapperService;
        $this->productImportRepository = $productImportRepository;
        $this->productPriceMapperService = $productPriceMapperService;
        $this->printPriceMapperService = $printPriceMapperService;
        $this->printDataMapperService = $printDataMapperService;
        $this->stockMapperService = $stockMapperService;
    }

    protected function updateCache(string $dataType, string $cacheKey): void
    {
        $url = $this->getEndpoint($dataType);

        try {
            if ($dataType == DataType::STOCK->value) {
                //Midocean stock endpoints ir redirects uz Amazon serveriem, Amazon aprēķina signature pret headeriem, ja uz redirect tiek norādīts Content-Type, tad izmet 403 Forbidden, tāpēc to nepieciešams noņemt
                $stack = HandlerStack::create();
                $stack->push(function (callable $handler) {
                    return function (RequestInterface $request, array $options) use ($handler) {
                        if ($request->getHeaderLine('Content-Type') && $request->getBody()->getSize() === 0) {
                            return $handler($request->withoutHeader('Content-Type'), $options);
                        }

                        return $handler($request, $options);
                    };
                });
                $response = $this->getClient()->get($url, [
                    'handler' => $stack,
                ]);
            } else {
                $response = $this->getClient()->get($url);
            }

            $this->info($url);
            if ($response->getStatusCode() === 200) {
                $content = $response->getBody()->getContents();

                Cache::put($cacheKey, [
                    'content'   => $content,
                    'cached_at' => Carbon::now()->toDateTimeString(),
                ], env('CACHE_TIME_TO_LIVE', 86400));
            } else {
                $this->error("Failed to fetch data for {$dataType}. HTTP Status: {$response->getStatusCode()}");
            }
        } catch (GuzzleException $exception) {
            $this->error("HTTP request failed for {$dataType}: {$exception->getMessage()}");
        }
    }

    /**
     * Process the content (JSON).
     *
     * Parses and processes the JSON content fetched from the API.
     *
     * @param  string  $dataType  Type of data being processed.
     * @param  string  $content  JSON content to be processed.
     */
    protected function processContent(string $dataType, string $content): void
    {
        // Decode JSON content
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log an error if JSON decoding fails
            $this->error("Failed to parse JSON data for {$dataType}.");

            return;
        }

        switch ($dataType) {
            case 'products':
                $this->info("Record count for {$dataType}: ".count($data));
                $this->processProductData($data);
                break;
            case 'product_prices':
                $this->info("Record count for {$dataType}: ".count($data['price']));
                $this->processProductPriceData($data);
                break;
            case 'print_prices':
                $this->info("Record count for {$dataType}: ".count($data['print_techniques']));
                $this->processPrintPriceData($data);
                break;
            case 'print_data':
                $this->info("Record count for {$dataType}: ".count($data['products']));
                $this->processPrintData($data);
                break;
            case 'stock':
                $this->processStockData($data);
                break;
            default:
                $this->error("Unknown data type {$dataType}.");
                break;
        }
    }

    private function processProductData(array $data): void
    {
        $this->productMapperService->loadData($data);
        $this->productMapperService->mapProducts();
        $this->productMapperService->mapProductFlats();
        $this->productMapperService->mapProductCategories();
        $this->productMapperService->mapProductAttributeValues();
        $this->productMapperService->mapAttributeOptions();
        $this->productMapperService->mapProductImageURLs();
    }

    private function processProductPriceData(array $data): void
    {
        $this->productPriceMapperService->loadData($data);
        $this->productPriceMapperService->mapProductAttributeValuePrices();
        $this->productPriceMapperService->mapProductFlatPrices();
        $this->productPriceMapperService->mapProductPriceIndices();
    }

    private function processPrintPriceData(array $data): void
    {
        $this->printPriceMapperService->loadData($data);
        $this->printPriceMapperService->mapPrintManipulations();
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
        return config('integrations.midocean.endpoints')[$dataType];
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
