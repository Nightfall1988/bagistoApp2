<?php

namespace App\Console\Commands;

use App\Repositories\Population\PopulationRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DownloadAndUpsertProductImages extends Command
{
    protected $signature = 'app:download-and-upsert-product-images';

    protected $description = 'Downloads and upserts product images';

    private PopulationRepository $repository;

    public function __construct(PopulationRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    public function handle(): void
    {
        $data = $this->repository->getDownloadableProductUrlImages();
        $total = $data->count();

        $this->output->progressStart($total);

        $data->chunk(10)->each(function (Collection $chunk) {
            $this->transformData($chunk);
            $this->output->progressAdvance($chunk->count());
        });

        $this->output->progressFinish();
    }

    protected function transformData(Collection $data): void
    {
        $transformedData = $data->map(function ($item) {
            $localPath = $this->downloadImage($item->url, $item->product_id);

            return [
                'product_id'          => $item->product_id,
                'position'            => $item->position,
                'type'                => $item->type,
                'path'                => $localPath,
                'downloaded_from_url' => $item->url,
            ];
        });

        $this->repository->upsertProductImages($transformedData->all());
    }

    protected function downloadImage(string $url, int $productId): string
    {
        $url = trim($url);
        $url = str_replace(' ', '%20', $url);

        $encodedUrl = filter_var($url, FILTER_VALIDATE_URL) ? $url : urlencode($url);

        $response = Http::withOptions([
            'verify' => ! env('BYPASS_CERTIFICATE', false),
        ])->get($encodedUrl);

        if ($response->successful()) {
            $fileName = 'product/'.$productId.'/'.basename($encodedUrl);
            Storage::disk('public')->put($fileName, $response->body());

            return $fileName;
        }

        throw new \Exception("Failed to download image: $url");
    }
}
