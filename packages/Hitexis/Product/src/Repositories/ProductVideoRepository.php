<?php

namespace Hitexis\Product\Repositories;

class ProductVideoRepository extends ProductMediaRepository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Hitexis\Product\Contracts\ProductVideo';
    }

    /**
     * Upload videos.
     *
     * @param  array  $data
     * @param  \Hitexis\Product\Contracts\Product  $product
     * @return void
     */
    public function uploadVideos($data, $product)
    {
        $this->upload($data, $product, 'videos');
    }
}
