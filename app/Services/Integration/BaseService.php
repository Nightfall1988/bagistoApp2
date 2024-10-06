<?php
namespace App\Services\Integration;

class BaseService
{
    protected array $data;
    public function loadData(array $data): void
    {
        $this->data = $data;
    }
}
