<?php

namespace App\Console\Commands\Integration;

use App\Enums\Integrations\Midocean\CacheKey;
use App\Enums\Integrations\Midocean\DataType;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryTranslation;
use Webkul\Core\Models\Locale;

class ExtractMidoceanCategories extends AbstractCategoryExtractCommand
{
    protected $signature = 'extract:midocean-categories';

    protected $description = 'Extract unique categories from Midocean JSON data stored in Redis cache';

    protected function getSupplierName(): string
    {
        return 'Midocean';
    }

    protected function checkAndLoadDataFromCache(): mixed
    {
        $productsDatatype = DataType::tryFrom(DataType::PRODUCTS->value);
        $cacheKey = CacheKey::getCacheKeyFromDataType($productsDatatype)->value;

        $cachedData = Cache::get($cacheKey);
        $data = optional($cachedData)['content'];

        if (! $data) {
            $url = config('integrations.midocean.endpoints')[$productsDatatype->value];

            try {
                $client = new Client([
                    'headers' => [
                        'Content-Type'     => 'application/json',
                        'x-Gateway-APIKey' => config('integrations.midocean.auth.api-key'),
                    ],
                ]);
                $response = $client->get($url);

                if ($response->getStatusCode() !== 200) {
                    $this->error("Failed to fetch data for {$productsDatatype->value}. HTTP Status: {$response->getStatusCode()}");

                    return 1;
                }

                $content = $response->getBody()->getContents();
                $data = $content;

                Cache::put($cacheKey, [
                    'content'   => $content,
                    'cached_at' => now()->toDateTimeString(),
                ], env('CACHE_TIME_TO_LIVE', 86400));

            } catch (GuzzleException $exception) {
                $this->error("HTTP request failed for {$productsDatatype->value}: {$exception->getMessage()}");

                return 1;
            }
        }

        if (! $data) {
            $this->error("No valid data found in Redis under the key '{$cacheKey}'");

            return 1;
        }

        $jsonData = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON data found in Redis');

            return 1;
        }

        return $jsonData;
    }

    protected function transformData(mixed $jsonData): array
    {
        $uniqueCategories = [];

        foreach ($jsonData as $item) {
            if (isset($item['variants'])) {
                foreach ($item['variants'] as $variant) {
                    $this->extractCategories($variant, $uniqueCategories);
                }
            }
        }

        return $uniqueCategories;
    }

    protected function insertTransformedData(array $categories): void
    {
        $enLocaleId = Locale::where('code', 'en')->first()->id;
        foreach ($categories as $categoryData) {
            $translation = CategoryTranslation::where('slug', $categoryData['slug'])->first();

            if (! $translation) {
                $category = Category::create([
                    'status'    => 1,
                ]);
                $category->translations()->create([
                    'slug'                => $categoryData['slug'],
                    'name'                => $categoryData['name'],
                    'locale'              => 'en',
                    'locale_id'           => $enLocaleId,
                ]);
            }
        }
    }

    protected function extractCategories(array $variant, array &$uniqueCategories): void
    {
        $existingSlugs = array_column($uniqueCategories, 'slug');

        foreach ($variant as $key => $value) {
            if (stripos($key, 'category_level') !== false && ! empty($value)) {
                $category = trim($value);
                $slug = Str::slug($category);

                if (! in_array($slug, $existingSlugs)) {
                    $uniqueCategories[] = [
                        'slug' => $slug,
                        'name' => $category,
                    ];
                    $existingSlugs[] = $slug;
                }
            }
        }
    }
}
