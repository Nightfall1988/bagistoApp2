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

    const LV_CATEGORIES=[
        'officewriting'          => 'Biroja piederumi',
        'officeaccessories'      => 'Biroja aksesuāri',
        'bagstravel'             => 'Somas un ceļojuma piederumi',
        'backpacksbusinessbags'  => 'Mugursomas un darba somas',
        'premiumstools'          => 'Premiums & Tools',
        'keyrings'               => 'Atslēgu piekariņi',
        'notebooks'              => 'Piezīmju grāmatiņas',
        'christmaswinter'        => 'Christmas & Winter',
        'textile'                => 'Tekstils',
        'decoration'             => 'Dekorācijas',
        'drinkware'              => 'Dzērienu trauki',
        'giftbag'                => 'Dāvanu maisiņi',
        'kidsgames'              => 'Bērniem un spēles',
        'stuffedanimals'         => 'Mīkstās rotaļlietas',
        'eatingdrinking'         => 'Ēšanai un dzeršanai',
        'kitchenware'            => 'Virtuves piederumi',
        'wellnesscare'           => 'Veselība un kopšana',
        'homeliving'             => 'Māja un dzīvesstils',
        'shoppingbags'           => 'Iepirkuma somas',
        'others'                 => 'Citi',
        'catalogues'             => 'Katalogi',
        'umbrellasraingarments'  => 'Lietussargi un lietus apģērbi',
        'raingear'               => 'Lietus inventārs',
        'painting'               => 'Gleznošana',
        'antistresscandies'      => 'Pret stresu/Konfektes',
        'sportsrecreation'       => 'Sports un atpūta',
        'beachitems'             => 'Pludmales preces',
        'sportoutdoorbags'       => 'Sporta un āra somas',
        'travelaccessories'      => 'Ceļojumu piederumi',
        'outdoor'                => 'Brīvā dabā',
        'apparelaccessories'     => 'Apģērbi un aksesuāri',
        'accessories'            => 'Aksesuāri',
        'personalcare'           => 'Personīgā aprūpe',
        'barware'                => 'Bāra piederumi',
        'toolstorches'           => 'Instrumenti un lāpas',
        'writing'                => 'Rakstīšana',
        'portfolios'             => 'Portfeļi',
        'games'                  => 'Spēles',
        'caraccessories'         => 'Auto piederumi',
        'headgear'               => 'Galvas piederumi',
        'events'                 => 'Pasākumi',
        'umbrellas'              => 'Lietussargi',
        'sporthealth'            => 'Sports un veselība',
        'firstaid'               => 'Pirmā palīdzība',
        'technologyaccessories'  => 'Tehnoloģijas un piederumi',
        'usbs'                   => 'USB',
        'wirelesschargers'       => 'Bezvadu lādētāji',
        'audiosound'             => 'Audio un skaņa',
        'phoneaccessories'       => 'Tālruņu piederumi',
        'lunchware'              => 'Pusdienu trauki',
        'powerbanks'             => 'Ārējie lādētāji',
        'ballpens'               => 'Pildspalvas',
        'textilecategory'        => 'Tekstils',
        'textilesologroup'       => 'Tekstils no SOLO Grupas',
        'corporateworkwear'      => 'Korporatīvie un Darba apģērbi',
        'brand'                  => 'Brenda',
        'windproofumbrellas'     => 'Vēja necaurlaidīgi lietussargi',
    ];

    protected function insertTransformedData(array $categories): void
    {
        $enLocaleId = Locale::where('code', 'en')->first()->id;
        $lvLocaleId = Locale::where('code', 'lv')->first()->id;

        foreach ($categories as $categoryData) {
            $parentCategory = null;
            if (! empty($categoryData['parent_slug'])) {
                $parentTranslation = CategoryTranslation::where('slug', $categoryData['parent_slug'])->first();
                $parentCategory = $parentTranslation ? $parentTranslation->category : null;
            }

            $translation = CategoryTranslation::where('slug', $categoryData['slug'])->first();

            if (! $translation) {
                $category = Category::create([
                    'status'    => 1,
                    'parent_id' => $parentCategory ? $parentCategory->id : null,
                ]);

                $category->translations()->create([
                    'slug'      => $categoryData['slug'],
                    'name'      => $categoryData['name'],
                    'locale'    => 'en',
                    'locale_id' => $enLocaleId,
                ]);

                $transformedSlug = str_replace('-', '', $categoryData['slug']);

                if (isset(self::LV_CATEGORIES[$transformedSlug])) {
                    $category->translations()->create([
                        'slug'      => Str::slug(trim(self::LV_CATEGORIES[$transformedSlug])),
                        'name'      => self::LV_CATEGORIES[$transformedSlug],
                        'locale'    => 'lv',
                        'locale_id' => $lvLocaleId,
                    ]);
                }
            }
        }
    }

    protected function extractCategories(array $variant, array &$uniqueCategories): void
    {
        $previousCategory = null;
        foreach ($variant as $key => $value) {
            if (stripos($key, 'category_level') !== false && ! empty($value)) {
                $category = trim($value);
                $slug = Str::slug($category);

                if (! $this->categoryExists($slug, $uniqueCategories)) {
                    $uniqueCategories[] = [
                        'slug'        => $slug,
                        'name'        => $category,
                        'parent_slug' => $previousCategory ? $previousCategory['slug'] : null,
                    ];
                }
                $previousCategory = ['slug' => $slug, 'name' => $category];
            }
        }
    }

    protected function categoryExists(string $slug, array $categories): bool
    {
        foreach ($categories as $category) {
            if ($category['slug'] === $slug) {
                return true;
            }
        }

        return false;
    }
}
