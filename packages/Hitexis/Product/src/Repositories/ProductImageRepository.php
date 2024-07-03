<?php

namespace Hitexis\Product\Repositories;

use Illuminate\Container\Container;
use Hitexis\Product\Repositories\ProductMediaRepository;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Illuminate\Support\Facades\Storage;

class ProductImageRepository extends ProductMediaRepository
{
    protected $productRepository;
    public array $fileList;
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        HitexisProductRepository $productRepository,
        Container $container
    ) {
        parent::__construct($container);
        $this->productRepository = $productRepository;
    }

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Hitexis\Product\Models\ProductImage';
    }

    /**
     * Upload images.
     *
     * @param  array  $data
     * @param  \Hitexis\Product\Models\Product  $product
     */
    public function uploadImages($data, $product): void
    {
        $this->upload($data, $product, 'images');

        if (isset($data['variants'])) {
            $this->uploadVariantImages($data['variants']);
        }
    }

    /**
     * Upload images products imported from Midocean  .
     *
     * @param  array  $data
     * @param  \Hitexis\Product\Models\Product  $product
     */
    public function uploadImportedImagesMidocean($data) {
        return $this->uploadDigitalAssets($data);
    }
    /**
     * Upload images products imported from Stricker  .
     *
     * @param  array  $data
     * @param  \Hitexis\Product\Models\Product  $product
     */
    public function uploadImportedImagesStricker($data, $product) : void {
        
    }

    /**
     * Upload images products imported from XDConnects  .
     *
     * @param  array  $data
     * @param  \Hitexis\Product\Models\Product  $product
     */
    public function uploadImportedImagesXDConnects($data, $product) {
        foreach ($data as $imgUrl) {
            if (filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                $data = $this->downloadAndUploadImage($imgUrl);
                $list[] = $data['file'];
                $tempPaths[] = $data['tempPath'];
            }
        }
        return ['fileList' => $list, 'tempPaths' => $tempPaths];
    }

    // MIDOCEAN
    /**
     * Upload images products imported from Midocean.
     *
     * @param  array  $digitalAssets
     * @param  \Hitexis\Product\Models\Product  $product
     */
    public function uploadDigitalAssets($digitalAssets)
    {
        $list = [];

        $tempPaths = [];
        foreach ($digitalAssets as $asset) {
            if (isset($asset->url) && filter_var($asset->url, FILTER_VALIDATE_URL)) {
                $data = $this->downloadAndUploadImage($asset->url);
                $list[] = $data['file'];
                $tempPaths[] = $data['tempPath'];
            }
        }

        return ['fileList' => $list, 'tempPaths' => $tempPaths];
    }

    private function downloadAndUploadImage($url)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get($url);

        $imageContent = $response->getBody()->getContents();
        $originalName = basename(parse_url($url, PHP_URL_PATH));

        $tempPath = sys_get_temp_dir() . '/' . $originalName;
        file_put_contents($tempPath, $imageContent);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempPath,
            $originalName,
            $response->getHeaderLine('Content-Type'),
            null,
            true
        );

        return ['file' => $uploadedFile, 'tempPath' => $tempPath];
    }

    // STRICKER

    public function assignImage($imageName) {

        if ($imageName != '') {
            $folder = 'storage/app/public/product/stricker/';
            $matchingFiles = [];
            $pathToFile = $folder . $imageName;
            $searchPattern = $folder . $imageName . '*';
            $matchingFiles = glob($searchPattern);
            
            if (!empty($matchingFiles)) {
            foreach ($matchingFiles as $file) {
                    $mimeType = Storage::mimeType($file);

                    $uploadedFile = new \Illuminate\Http\UploadedFile(
                        $pathToFile,
                        $imageName,
                        $mimeType,
                        null,
                        true 
                    );

                    $images[] = $uploadedFile;
                }
            
                return ['files' => $images, 'tempPath' => $pathToFile];
            }
        } else {
            return 0;
        }
    }

    // XDCONNECTS


    /**
     * Upload variant images.
     *
     * @param  array  $variants
     */
    public function uploadVariantImages($variants): void
    {
        foreach ($variants as $variantsId => $variantData) {
            $product = $this->productRepository->find($variantsId);

            if (! $product) {
                break;
            }

            $this->upload($variantData, $product, 'images');
        }
    }

    public function deleteTempImages($paths) {
        foreach ($paths as $path) {
            unlink($path);
        }
    }
}
