<?php

namespace App\Console\Commands\Integration;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

abstract class AbstractProductImportCommand extends Command
{
    /**
     * Cache Time-To-Live (TTL) in seconds.
     * @const int
     */
    public const CACHE_TTL = 86400; // 1 day

    /**
     * Execute the console command.
     *
     * Primary entry point for the command when executed.
     * Orchestrates the cache checking and data processing.
     */
    public function handle(): void
    {
        $this->checkAndWriteOrUpdateCache();
        $this->processCachedData();
    }

    /**
     * Check the cache and update if necessary.
     *
     * Iterates over the data types, checks the cache for each,
     * and updates the cache if the data is stale or not present.
     */
    protected function checkAndWriteOrUpdateCache(): void
    {
        foreach ($this->getDataTypes() as $dataType) {
            $cacheKey = $this->getCacheKey($dataType);
            $cachedData = Cache::get($cacheKey);
            $cachedAt = isset($cachedData['cached_at']) ? Carbon::parse($cachedData['cached_at']) : null;

            if ($cachedData && $cachedAt && $cachedAt->diffInSeconds(Carbon::now()) < self::CACHE_TTL) {
                $this->info("Using cached data for {$dataType}. Cached at {$cachedAt}.");
            } else {
                $this->info("Fetching new data for {$dataType}.");
                $this->updateCache($dataType, $cacheKey);
            }
        }
    }

    /**
     * Write or Update the cache with fresh data from the API.
     *
     * @param string $dataType The type of data being cached.
     * @param string $cacheKey The cache key used to store data.
     */
    protected function updateCache(string $dataType, string $cacheKey): void
    {
        $url = $this->getEndpoint($dataType);

        try {
            $response = $this->client->get($url);

            if ($response->getStatusCode() === 200) {
                $content = $response->getBody()->getContents();

                Cache::put($cacheKey, [
                    'content'   => $content,
                    'cached_at' => Carbon::now()->toDateTimeString(),
                ], self::CACHE_TTL);
            } else {
                $this->error("Failed to fetch data for {$dataType}. HTTP Status: {$response->getStatusCode()}");
            }
        } catch (GuzzleException $exception) {
            $this->error("HTTP request failed for {$dataType}: {$exception->getMessage()}");
        }
    }

    /**
     * Process the cached data.
     *
     * Iterates over the cache keys, retrieves cached content,
     * and processes it for each data type.
     */
    protected function processCachedData(): void
    {
        foreach ($this->getCacheKeys() as $cacheKey) {
            $cachedData = Cache::get($cacheKey);

            if ($cachedData && isset($cachedData['content'])) {
                $dataType = $this->getDataTypeFromCacheKey($cacheKey);
                $this->processContent($dataType, $cachedData['content']);
            }
        }
    }

    /**
     * Process the content (either XML or JSON).
     *
     * @param string $dataType Type of data being processed.
     * @param string $content Content to be processed.
     */
    abstract protected function processContent(string $dataType, string $content): void;

    /**
     * Get a list of data types to process.
     *
     * Returns an array of data types that will be processed by the command.
     * Each data type corresponds to a specific entity or dataset.
     *
     * @return array List of data types.
     */
    abstract protected function getDataTypes(): array;

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
    abstract protected function getCacheKey(string $dataType): string;

    /**
     * Get the endpoint URL for a given data type.
     *
     * Returns the URL endpoint to fetch the data for the given data type
     * from the external API.
     *
     * @param string $dataType The type of data being fetched.
     * @return string URL endpoint.
     */
    abstract protected function getEndpoint(string $dataType): string;

    /**
     * Get all cache keys.
     *
     * Returns an array of all cache keys used by the command.
     * Each key in the array maps to a specific cache entry.
     *
     * @return array List of cache keys.
     */
    abstract protected function getCacheKeys(): array;

    /**
     * Map cache key to data type.
     *
     * Converts a cache key back to its corresponding data type.
     * This is useful for determining the data type when processing cached content.
     *
     * @param string $cacheKey Cache key used to store data.
     * @return string Data type.
     */
    abstract protected function getDataTypeFromCacheKey(string $cacheKey): string;

    /**
     * Get the HTTP client instance.
     *
     * Returns the configured HTTP client for making API requests.
     *
     * @return Client HTTP client instance.
     */
    abstract protected function getClient(): Client;
}
