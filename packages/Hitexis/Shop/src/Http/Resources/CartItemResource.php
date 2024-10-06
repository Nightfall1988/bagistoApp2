<?php

namespace Hitexis\Shop\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        if (!isset($this->resource->additional['technique-single-price']) || $this->resource->additional['technique-single-price'] =='') {
            return [
                'id'                        => $this->id,
                'quantity'                  => $this->quantity,
                'type'                      => $this->type,
                'name'                      => $this->name,
                'price_incl_tax'            => $this->price_incl_tax,
                'price'                     => $this->price,
                'formatted_price_incl_tax'  => core()->formatPrice($this->price_incl_tax),
                'formatted_price'           => core()->formatPrice($this->price),
                'total_incl_tax'            => $this->total_incl_tax,
                'total'                     => $this->total,
                'formatted_total_incl_tax'  => core()->formatPrice($this->total_incl_tax),
                'formatted_total'           => core()->formatPrice($this->total),
                'discount_amount'           => $this->discount_amount,
                'formatted_discount_amount' => core()->formatPrice($this->discount_amount),
                'options'                   => array_values($this->resource->additional['attributes'] ?? []),
                'base_image'                => $this->getTypeInstance()->getBaseImage($this),
                'product_url_key'           => $this->product->url_key,
                'wholesale'                 => $this->product->wholesales,
            ];
        } else {
            return [
                'id'                        => $this->id,
                'quantity'                  => $this->quantity,
                'type'                      => $this->type,
                'name'                      => $this->name,
                'price_incl_tax'            => $this->price_incl_tax,
                'price'                     => $this->price,
                'formatted_price_incl_tax'  => core()->formatPrice($this->price_incl_tax),
                'formatted_price'           => core()->formatPrice($this->price),
                'total_incl_tax'            => $this->total_incl_tax,
                'total'                     => $this->total,
                'formatted_total_incl_tax'  => core()->formatPrice($this->total_incl_tax),
                'formatted_total'           => core()->formatPrice($this->total),
                'discount_amount'           => $this->discount_amount,
                'formatted_discount_amount' => core()->formatPrice($this->discount_amount),
                'options'                   => array_values($this->resource->additional['attributes'] ?? []),
                'base_image'                => $this->getTypeInstance()->getBaseImage($this),
                'product_url_key'           => $this->product->url_key,
                'print_fee'                 => $this->resource->additional['technique-single-price'],
                'printPriceFull'            => $this->resource->additional['technique-price'] ?? '',
                'wholesale'                 => $this->product->wholesales,
            ];
        }
    }
}
