<?php

namespace Hitexis\Product\Repositories;
use Webkul\Core\Eloquent\Repository;

class SupplierRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Hitexis\Product\Models\ProductSupplier';
    }
}