<?php

namespace App\Console\Commands\Integration;

use App\Enums\Integrations\Stricker\CacheKey;
use App\Enums\Integrations\Stricker\DataType;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class StrickerProductImport extends AbstractProductImportCommand
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'integration:stricker:import';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Stricker Product import';

    /**
     * HTTP Client instance
     * @var Client
     */
    protected Client $client;

    /**
     * Authentication token for API requests
     * @var string|null
     */
    protected ?string $authToken = null;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        // Initialize the HTTP client with default headers
        $this->client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);

        // Perform authentication
        $this->authenticate();
    }

    /**
     * Authenticate and set the auth token.
     *
     * Fetches the auth token and sets it for future requests.
     */
    protected function authenticate(): void
    {
        try {
            // Fetch the authentication token
            $response = $this->client->get(config('integrations.stricker.auth.url').config('integrations.stricker.auth.token'));
            if ($authToken = json_decode($response->getBody()->getContents())->Token) {
                $this->authToken = $authToken;
            }
        } catch (GuzzleException $exception) {
            $this->error("Authentication request failed: {$exception->getMessage()}");
        }
    }

    /**
     * Process the content (JSON).
     *
     * Parses and processes the JSON content fetched from the API.
     *
     * @param string $dataType Type of data being processed.
     * @param string $content JSON content to be processed.
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

        // Log the record count in the JSON data
        $this->info("Record count for {$dataType}: " . count($data));

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
        $endpointConfig = config('integrations.stricker.endpoints');

        if (!isset($endpointConfig[$dataType])) {
            throw new \InvalidArgumentException("No endpoint configured for data type: {$dataType}");
        }

        return $endpointConfig[$dataType] . $this->authToken . '&lang=en';
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
