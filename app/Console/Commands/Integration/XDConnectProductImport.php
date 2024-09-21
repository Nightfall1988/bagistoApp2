<?php

namespace App\Console\Commands\Integration;

use App\Enums\Integrations\XDConnect\CacheKey;
use App\Enums\Integrations\XDConnect\DataType;
use GuzzleHttp\Client;
use SimpleXMLElement;

class XDConnectProductImport extends AbstractProductImportCommand
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'integration:xdconnect:import';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'XD Connects Product import';

    /**
     * HTTP Client instance
     * @var Client
     */
    protected Client $client;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->client = new Client([
            'headers' => [
                'Content-Type'     => 'application/json',
            ]
        ]);
    }

    /**
     * Process the content (XML).
     *
     * Parse and process the XML content fetched from the API.
     *
     * @param string $dataType Type of data being processed.
     * @param string $content XML content to be processed.
     */
    protected function processContent(string $dataType, string $content): void
    {
        try {
            // Parse the XML content
            $xml = new SimpleXMLElement($content);
        } catch (\Exception $e) {
            // Log an error if XML parsing fails
            $this->error("Failed to parse XML data for {$dataType}: " . $e->getMessage());
            return;
        }

        // Log the record count in the XML
        $this->info("Record count for {$dataType}: " . $xml->count());

        // TODO: Process content, normalize, transform, map, etc., and store somewhere or pass directly for further processing
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
        return array_map(fn($dataType) => $dataType->value, DataType::cases());
    }

    /**
     * Get the cache key for a given data type.
     *
     * Returns a cache key string that uniquely identifies the cache entry
     * for the given data type. This key is used to store and retrieve
     * cached data.
     *
     * @param string $dataType The type of data being cached.
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
     * @param string $dataType The type of data being fetched.
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
        return array_map(fn($cacheKey) => $cacheKey->value, CacheKey::cases());
    }

    /**
     * Map cache key to data type.
     *
     * Converts a cache key back to its corresponding data type.
     * This is useful for determining the data type when processing cached content.
     *
     * @param string $cacheKey Cache key used to store data.
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
