<?php

namespace Hitexis\Checkout;

use Illuminate\Support\Facades\Event;
use Hitexis\Checkout\Contracts\CartAddress as CartAddressContract;
use Hitexis\Checkout\Exceptions\BillingAddressNotFoundException;
use Hitexis\Checkout\Models\CartAddress;
use Hitexis\Checkout\Models\CartPayment;
use Hitexis\Checkout\Repositories\CartAddressRepository;
use Hitexis\Checkout\Repositories\CartItemRepository;
use Hitexis\Checkout\Repositories\CartRepository;
use Webkul\Customer\Contracts\Customer as CustomerContract;
use Webkul\Customer\Contracts\Wishlist as WishlistContract;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Customer\Repositories\WishlistRepository;
use Hitexis\Product\Contracts\Product as ProductContract;
use Hitexis\Product\Repositories\HitexisProductRepository as ProductRepository;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Tax\Facades\Tax;
use Webkul\Tax\Repositories\TaxCategoryRepository;
use Hitexis\PrintCalculator\Repositories\PrintTechniqueRepository;

class Cart
{
    /**
     * @var \Hitexis\Checkout\Contracts\Cart
     */
    private $cart;

    private $flag = 0;

    private array $printData = ["technique-info" => '', "technique-price" => '', 'technique-single-price' => '', 'setup-price' => '', 'print-manipulation' => ''];

    const TAX_CALCULATION_BASED_ON_SHIPPING_ORIGIN = 'shipping_origin';

    const TAX_CALCULATION_BASED_ON_BILLING_ADDRESS = 'billing_address';

    const TAX_CALCULATION_BASED_ON_SHIPPING_ADDRESS = 'shipping_address';

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(
        protected CartRepository $cartRepository,
        protected CartItemRepository $cartItemRepository,
        protected CartAddressRepository $cartAddressRepository,
        protected ProductRepository $productRepository,
        protected TaxCategoryRepository $taxCategoryRepository,
        protected WishlistRepository $wishlistRepository,
        protected CustomerAddressRepository $customerAddressRepository,
        protected PrintTechniqueRepository $printTechniqueRepository
    ) {

        $this->initCart();
    }

    /**
     * Initialize cart
     */
    public function initCart(?CustomerContract $customer = null): void
    {
        if (! $customer) {
            $customer = auth()->guard()->user();
        }

        if ($customer) {
            $this->cart = $this->cartRepository->findOneWhere([
                'customer_id' => $customer->id,
                'is_active'   => 1,
            ]);
        } elseif (session()->has('cart')) {
            $this->cart = $this->cartRepository->find(session()->get('cart')->id);
        }
    }

    /**
     * Returns cart
     */
    public function refreshCart(): void
    {
        if (! $this->cart) {
            return;
        }

        $this->cart = $this->cartRepository->find($this->cart->id);
    }

    /**
     * Set cart
     */
    public function setCart(Contracts\Cart $cart): void
    {
        $this->cart = $cart;

        if ($this->cart->customer) {
            return;
        }

        $cartTemp = new \stdClass();
        $cartTemp->id = $this->cart->id;

        session()->put('cart', $cartTemp);
    }

    /**
     * Returns cart.
     */
    public function getCart(): ?Contracts\Cart
    {
        return $this->cart;
    }

    /**
     * Create new cart instance.
     */
    public function createCart(array $data): ?Contracts\Cart
    {
        $data = array_merge([
            'is_guest'              => 1,
            'channel_id'            => core()->getCurrentChannel()->id,
            'global_currency_code'  => $baseCurrencyCode = core()->getBaseCurrencyCode(),
            'base_currency_code'    => $baseCurrencyCode,
            'channel_currency_code' => core()->getChannelBaseCurrencyCode(),
            'cart_currency_code'    => core()->getCurrentCurrencyCode(),
        ], $data);

        $customer = $data['customer'] ?? auth()->guard()->user();

        if ($customer) {
            $data = array_merge($data, [
                'is_guest'            => 0,
                'customer_id'         => $customer->id,
                'customer_first_name' => $customer->first_name,
                'customer_last_name'  => $customer->last_name,
                'customer_email'      => $customer->email,
            ]);
        }

        $cart = $this->cartRepository->create($data);

        $this->setCart($cart);

        return $cart;
    }

    /**
     * Remove cart and destroy the session
     */
    public function removeCart(Contracts\Cart $cart): void
    {
        $this->cartRepository->delete($cart->id);

        if (session()->has('cart')) {
            session()->forget('cart');
        }

        $this->resetCart();
    }

    /**
     * Reset cart
     */
    public function resetCart(): void
    {
        $this->cart = null;
    }

    /**
     * Activate the cart by id.
     */
    public function activateCart(int $cartId): void
    {
        $cart = $this->cartRepository->update([
            'is_active' => true,
        ], $cartId);

        $this->setCart($cart);
    }

    /**
     * Deactivates current cart.
     */
    public function deActivateCart(): void
    {
        if (! $this->cart) {
            return;
        }

        $this->cartRepository->update(['is_active' => false], $this->cart->id);

        $this->resetCart();

        if (session()->has('cart')) {
            session()->forget('cart');
        }
    }

    /**
     * This method handles when guest has some of cart products and then logs in.
     */
    public function mergeCart(CustomerContract $customer): void
    {
        if (! session()->has('cart')) {
            return;
        }

        $cart = $this->cartRepository->findOneWhere([
            'customer_id' => $customer->id,
            'is_active'   => 1,
        ]);

        $guestCart = $this->cartRepository->find(session()->get('cart')->id);

        /**
         * When the logged in customer is not having any of the cart instance previously and are active.
         */
        if (! $cart) {
            $this->cartRepository->update([
                'customer_id'         => $customer->id,
                'is_guest'            => 0,
                'customer_first_name' => $customer->first_name,
                'customer_last_name'  => $customer->last_name,
                'customer_email'      => $customer->email,
            ], $guestCart->id);

            session()->forget('cart');

            return;
        }

        $this->setCart($cart);

        foreach ($guestCart->items as $guestCartItem) {
            try {
                $this->addProduct($guestCartItem->product, $guestCartItem->additional);
            } catch (\Exception $e) {
                //Ignore exception
            }
        }

        $this->collectTotals();

        $this->removeCart($guestCart);
    }


    /**
     * Add items in a cart with some cart and item details.
     */
    public function addProduct(ProductContract $product, array $data): Contracts\Cart|\Exception
    {
        if (
                array_key_exists("technique-info",$data) &&
                array_key_exists("technique-price",$data) &&
                $data["technique-info"] != '' &&
                $data["technique-price"] != ''
            ) {
                $this->printData["technique-info"] = $data["technique-info"];
                $this->printData["technique-single-price"] = $data["technique-single-price"];
                $this->printData["technique-price"] = $data['technique-price'];
                $this->printData["setup-price"] = $data["setup-price"];
                $this->printData["print-manipulation"] = $data["print-manipulation"];
                $this->printData["print-manipulation-single-price"] = $data["print-manipulation"];
            }
        Event::dispatch('checkout.cart.add.before', $product->id);

        if (! $this->cart) {
            $this->createCart([]);
        }

        $cartProducts = $product->getTypeInstance()->prepareForCart($data);
        if (is_string($cartProducts)) {
            if (! $this->cart->all_items->count()) {
                $this->removeCart($this->cart);
            } else {
                $this->collectTotals();
            }

            throw new \Exception($cartProducts);
        } else {

            $parentCartItem = null;

            foreach ($cartProducts as $cartProduct) {
                $cartItem = $this->getItemByProduct($cartProduct, $data);

                if (isset($cartProduct['parent_id'])) {
                    $cartProduct['parent_id'] = $parentCartItem->id;
                }

                if (! $cartItem) {

                    $cartItem = $this->cartItemRepository->create(array_merge($cartProduct, ['cart_id' => $this->cart->id]));

                    if (sizeof($product->wholesales) > 0) {
                        $wholesale = $this->getBestWholesalePromotion($cartItem, $product->wholesales);

		        if ($wholesale) {
                            $cartItem  = $this->transformCartItemByWholesale($cartItem, $wholesale);
                            $cartProduct = $this->transformCartItemToCartProduct($cartProduct,$cartItem);
                            $cartItem = $this->cartItemRepository->update($cartProduct, $cartItem->id);
                        }
                    }

                } else {
                    if (
                        isset($cartProduct['parent_id'])
                        && $cartItem->parent_id !== $parentCartItem->id
                    ) {
                        $cartItem = $this->cartItemRepository->create(array_merge($cartProduct, [
                            'cart_id' => $this->cart->id,
                        ]));
                    } else {

                        $cartItem = $this->cartItemRepository->update($cartProduct, $cartItem->id);

                        if (sizeof($product->wholesales) > 0) {
                            $wholesale = $this->getBestWholesalePromotion($cartItem, $product->wholesales);

                            if ($wholesale) {
                                $cartItem  = $this->transformCartItemByWholesale($cartItem, $wholesale);
                                $cartProduct = $this->transformCartItemToCartProduct($cartProduct,$cartItem);
                                $cartItem = $this->cartItemRepository->update($cartProduct, $cartItem->id);
                            }
                        }
                    }
                }

                if (! $parentCartItem) {
                    $parentCartItem = $cartItem;
                }
            }
        }

        $this->collectTotals();
        Event::dispatch('checkout.cart.add.after', $this->cart);

        return $this->cart;
    }

    function getBestWholesalePromotion($item, $wholesales)
    {
        $bestWholesalePromo = null;
        foreach ($wholesales as $wholesale) {
            if (isset($wholesale->batch_amount) && $wholesale->batch_amount <= $item->quantity) {
                $bestWholesalePromo = $wholesale;
            }
        }

        return $bestWholesalePromo;
    }


    public function transformCartItemByWholesale($item, $wholesale) : ?Contracts\CartItem {

        $tempTotal = $item->price * $item->quantity;

        if (!$wholesale) {
            $item->total = $tempTotal;
            $item->total_incl_tax = $item->total;
            $item->base_total = $item->total;
            $item->base_total_incl_tax = $item->total;
            $item->discount_amount = '0';
        } else {
            $discountFactor = (100 - $wholesale->discount_percentage) / 100;

            $item->price *= $discountFactor;
            $item->price_incl_tax *= $discountFactor;
            $item->base_price *= $discountFactor;
            $item->base_price_incl_tax *= $discountFactor;
            $item->total = $tempTotal * $discountFactor;
            $item->total_incl_tax = $item->total;
            $item->base_total = $item->total;
            $item->base_total_incl_tax = $item->total;
            $item->discount_amount = $tempTotal - $item->total;
        }

        return $item;
    }

    public function transformCartItemToCartProduct($cartProduct, $cartItem): array {
        $cartProduct['total'] = $cartItem->total;
        $cartProduct['total_incl_tax'] = $cartItem->total_incl_tax;
        $cartProduct['base_total'] = $cartItem->base_total;
        $cartProduct['base_total_incl_tax'] = $cartItem->base_total_incl_tax;
        return $cartProduct;
    }
    /**
     * Remove the item from the cart.
     */
    public function removeItem(int $itemId): bool
    {
        if (! $this->cart) {
            return false;
        }

        Event::dispatch('checkout.cart.delete.before', $itemId);

        Shipping::removeAllShippingRates();

        $result = $this->cartItemRepository->delete($itemId);

        Event::dispatch('checkout.cart.delete.after', $itemId);

        return $result;
    }

    public function getPriceBasedOnQuantity($quantity, $technique)
    {
        // Decode the JSON data from pricing_data
        $pricingData = json_decode($technique->pricing_data, true);

        // Sort pricing data by MinQt in ascending order
        usort($pricingData, function ($a, $b) {
            return $a['MinQt'] <=> $b['MinQt'];
        });

        // Initialize the default price in case the quantity is too small
        $price = null;

        // Iterate over pricing data to find the correct price for the quantity
        foreach ($pricingData as $pricing) {
            // Convert MinQt to integer for comparison
            $minQuantity = (int)str_replace('.', '', $pricing['MinQt']);

            // If the quantity is greater than or equal to MinQt, use this price
            if ($quantity >= $minQuantity) {
                $price = $pricing['Price'];
            } else {
                // Break when we exceed the quantity, since prices are in ascending order
                break;
            }
        }

        // Return the price or a default value if no price found
        return $price ?: 0;
    }


    /**
     * Get cart item by product.
     */
    public function getItemByProduct(array $data, ?array $parentData = null): ?Contracts\CartItem
    {
        $items = $this->cart->all_items;

        foreach ($items as $item) {

            if ($item->getTypeInstance()->compareOptions($item->additional, $data['additional'])) {
                if (! isset($data['additional']['parent_id'])) {


                    if (sizeof($this->productRepository->find($item->product_id)->first()->wholesales) != 0)
                    {
                        if ( isset($data['additional']['quantity'] ) &&  $data['additional']['quantity'] != 0)
                        {
                            $wholesales = $this->productRepository->find($item->product_id)->first()->wholesales;
                            $wholesale = $this->getBestWholesalePromotion($item, $wholesales);
                            $transformedItem  = $this->transformCartItemByWholesale($item, $wholesale);
                            // IN ABSTRACT TYPE THIS ADDS ONE MORE:
                            // getQtyRequest($data)

                            return $transformedItem;
                        }
                    }

                } else {
                    if ($item->parent->getTypeInstance()->compareOptions($item->parent->additional, $parentData ?: request()->all())) {
                        return $item;
                    }
                }


            }
        }

        return null;
    }

    /**
     * Update or create billing address.
     */
    public function saveAddresses(array $params): void
    {
        $this->updateOrCreateBillingAddress($params['billing']);

        $this->updateOrCreateShippingAddress($params['shipping'] ?? []);

        $this->setCustomerPersonnelDetails();

        $this->resetShippingMethod();
    }

    /**
     * Update or create billing address.
     */
    public function updateOrCreateBillingAddress(array $params): CartAddressContract
    {
        $params = collect($params)
            ->only([
                'use_for_shipping',
                'default_address',
                'registration_number',
                'company_name',
                'first_name',
                'last_name',
                'email',
                'address',
                'country',
                'state',
                'city',
                'postcode',
                'phone',
            ])
            ->merge([
                'address_type'      => CartAddress::ADDRESS_TYPE_BILLING,
                'parent_address_id' => ($params['address_type'] ?? '') == 'customer' ? $params['id'] : null,
                'cart_id'           => $this->cart->id,
                'customer_id'       => $this->cart->customer_id,
                'address'           => implode(PHP_EOL, $params['address']),
                'use_for_shipping'  => (bool) ($params['use_for_shipping'] ?? false),
            ])
            ->toArray();

        if ($this->cart->billing_address) {
            $address = $this->cartAddressRepository->update($params, $this->cart->billing_address->id);
        } else {
            $address = $this->cartAddressRepository->create($params);
        }

        $this->cart->setRelation('billing_address', $address);

        return $address;
    }

    /**
     * Update or create shipping address.
     */
    public function updateOrCreateShippingAddress(array $params): ?CartAddressContract
    {
        /**
         * If cart is not having any stockable items then no need to save shipping address.
         */
        if (! $this->cart->haveStockableItems()) {
            return null;
        }

        if (! $this->cart->billing_address) {
            throw new BillingAddressNotFoundException;
        }

        $fillableFields = [
            'default_address',
            'company_name',
            'registration_number',
            'first_name',
            'last_name',
            'email',
            'address',
            'country',
            'state',
            'city',
            'postcode',
            'phone',
        ];

        if ($this->cart->billing_address->use_for_shipping) {
            $params = $this->cart->billing_address->only($fillableFields);

            $params = array_merge($params, [
                'address_type'      => CartAddress::ADDRESS_TYPE_SHIPPING,
                'parent_address_id' => $this->cart->billing_address->parent_address_id,
                'cart_id'           => $this->cart->id,
                'customer_id'       => $this->cart->customer_id,
            ]);
        } else {
            if (empty($params)) {
                return null;
            }

            $params = collect($params)
                ->only($fillableFields)
                ->merge([
                    'address_type'      => CartAddress::ADDRESS_TYPE_SHIPPING,
                    'parent_address_id' => ($params['address_type'] ?? '') == 'customer' ? $params['id'] : null,
                    'cart_id'           => $this->cart->id,
                    'customer_id'       => $this->cart->customer_id,
                    'address'           => implode(PHP_EOL, $params['address']),
                ])
                ->toArray();
        }

        if ($this->cart->shipping_address) {
            $address = $this->cartAddressRepository->update($params, $this->cart->shipping_address->id);
        } else {
            $params['default_address'] = 0;

            $address = $this->cartAddressRepository->create($params);
        }

        $this->cart->setRelation('shipping_address', $address);

        return $address;
    }

    /**
     * Save customer details.
     */
    public function setCustomerPersonnelDetails(): void
    {
        $this->cart->customer_email = $this->cart->customer?->email ?? $this->cart->billing_address->email;
        $this->cart->customer_first_name = $this->cart->customer?->first_name ?? $this->cart->billing_address->first_name;
        $this->cart->customer_last_name = $this->cart->customer?->last_name ?? $this->cart->billing_address->last_name;

        $this->cart->save();
    }

    /**
     * Save shipping method for cart.
     */
    public function saveShippingMethod(string $shippingMethodCode): bool
    {
        if (! $this->cart) {
            return false;
        }

        if (! Shipping::isMethodCodeExists($shippingMethodCode)) {
            return false;
        }

        $this->cart->shipping_method = $shippingMethodCode;
        $this->cart->save();

        return true;
    }

    /**
     * Save shipping method for cart.
     */
    public function resetShippingMethod(): bool
    {
        if (! $this->cart) {
            return false;
        }

        Shipping::removeAllShippingRates();

        $this->cart->shipping_method = null;
        $this->cart->save();

        return true;
    }

    /**
     * Save payment method for cart.
     */
    public function savePaymentMethod(array $params): bool|Contracts\CartPayment
    {
        if (! $this->cart) {
            return false;
        }

        if ($cartPayment = $this->cart->payment) {
            $cartPayment->delete();
        }

        $cartPayment = new CartPayment;

        $cartPayment->method = $params['method'];
        $cartPayment->method_title = core()->getConfigData('sales.payment_methods.'.$params['method'].'.title');
        $cartPayment->cart_id = $this->cart->id;
        $cartPayment->save();

        return $cartPayment;
    }

    /**
     * Set coupon code to the cart.
     */
    public function setCouponCode(?string $code): self
    {
        $this->cart->coupon_code = $code;

        $this->cart->save();

        return $this;
    }

    /**
     * Set coupon code to the cart.
     */
    public function removeCouponCode(): self
    {
        return $this->setCouponCode(null);
    }

    /**
     * Move a wishlist item to cart.
     */
    public function moveToCart(WishlistContract $wishlistItem, ?int $quantity = 1): bool
    {
        if (! $wishlistItem->product->getTypeInstance()->canBeMovedFromWishlistToCart($wishlistItem)) {
            return false;
        }

        if (! $wishlistItem->additional) {
            $wishlistItem->additional = ['product_id' => $wishlistItem->product_id];
        }

        $additional = [
            ...$wishlistItem->additional,
            'quantity' => $quantity,
        ];

        $result = $this->addProduct($wishlistItem->product, $additional);

        if ($result) {
            $this->wishlistRepository->delete($wishlistItem->id);

            return true;
        }

        return false;
    }

    /**
     * Move to wishlist items.
     */
    public function moveToWishlist(int $itemId, int $quantity = 1): bool
    {
        $cartItem = $this->cart->items()->find($itemId);

        if (! $cartItem) {
            return false;
        }

        $wishlistItems = $this->wishlistRepository->findWhere([
            'customer_id' => $this->cart->customer_id,
            'product_id'  => $cartItem->product_id,
        ]);

        $found = false;

        foreach ($wishlistItems as $wishlistItem) {
            $options = $wishlistItem->item_options;

            if (! $options) {
                $options = ['product_id' => $wishlistItem->product_id];
            }

            if ($cartItem->getTypeInstance()->compareOptions($cartItem->additional, $options)) {
                $found = true;
            }
        }

        if (! $found) {
            $this->wishlistRepository->create([
                'channel_id'  => $this->cart->channel_id,
                'customer_id' => $this->cart->customer_id,
                'product_id'  => $cartItem->product_id,
                'additional'  => [
                    ...$cartItem->additional,
                    'quantity' => $quantity,
                ],
            ]);
        }

        if (! $this->cart->items->count()) {
            $this->cartRepository->delete($this->cart->id);

            $this->refreshCart();
        } else {
            $this->cartItemRepository->delete($itemId);

            $this->refreshCart();

            $this->collectTotals();
        }

        return true;
    }

    /**
     * Checks if cart has any error.
     */
    public function hasError(): bool
    {
        if (
            ! $this->cart
            || ! $this->isItemsHaveSufficientQuantity()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Checks if all cart items have sufficient quantity.
     */
    public function isItemsHaveSufficientQuantity(): bool
    {
        if (! $this->cart) {
            return false;
        }

        foreach ($this->cart->items as $item) {
            if (! $this->isItemHaveQuantity($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if all cart items have sufficient quantity.
     */
    public function isItemHaveQuantity(Contracts\CartItem $item): bool
    {
        return $item->getTypeInstance()->isItemHaveQuantity($item);
    }

    /**
     * Updates cart totals.
     */
    public function collectTotals(): self
    {
        if (! $this->validateItems()) {
            $this->refreshCart();
        }

        if (! $this->cart) {
            return $this;
        }

        Event::dispatch('checkout.cart.collect.totals.before', $this->cart);

        $this->calculateItemsTax();
        $this->calculateShippingTax();

        $this->refreshCart();

        // Reset totals
        $this->cart->sub_total = $this->cart->base_sub_total = 0;
        $this->cart->sub_total_incl_tax = $this->cart->base_sub_total_incl_tax = 0;
        $this->cart->grand_total = $this->cart->base_grand_total = 0;
        $this->cart->tax_total = $this->cart->base_tax_total = 0;
        $this->cart->discount_amount = $this->cart->base_discount_amount = 0;
        $this->cart->shipping_amount = $this->cart->base_shipping_amount = 0;
        $this->cart->shipping_amount_incl_tax = $this->cart->base_shipping_amount_incl_tax = 0;
        $this->cart->print_price = 0; // Reset print price

        $quantities = 0;

        foreach ($this->cart->items as $item) {

            // Fetch print data for each item
            $printPrice = floatval($item->print_price);

            // Update totals for discount, tax, and print price
            $this->cart->discount_amount += $item->discount_amount;
            $this->cart->base_discount_amount += $item->base_discount_amount;

            // Calculate the total amount for tax including print price
            $itemTotalForTax = (float) ($item->total + $printPrice);
            $itemBaseTotalForTax = (float) ($item->base_total + $item->base_print_price);

            // Accumulate subtotal and include print price
            $this->cart->sub_total += $itemTotalForTax;
            $this->cart->base_sub_total += $itemBaseTotalForTax;

            $this->cart->print_price += $printPrice;
            $this->cart->print_name = $item->print_name;
            $this->cart->print_setup = $item->print_setup;
            $this->cart->print_manipulation += $item->print_manipulation_cost;

            // Calculate tax based on the combined product price and print price
            $taxPercent = $item->tax_percent;
            $itemTaxAmount = ($itemTotalForTax * $taxPercent) / 100;
            $itemBaseTaxAmount = ($itemBaseTotalForTax * $taxPercent) / 100;

            // Add item tax to the cart's total tax
            $this->cart->tax_total += $itemTaxAmount;
            $this->cart->base_tax_total += $itemBaseTaxAmount;

            // Calculate item totals including tax
            $this->cart->sub_total_incl_tax += $itemTotalForTax + $itemTaxAmount;
            $this->cart->base_sub_total_incl_tax += $itemBaseTotalForTax + $itemBaseTaxAmount;

            $quantities += $item->quantity;
        }

        // Set item quantities and count
        $this->cart->items_qty = $quantities;
        $this->cart->items_count = $this->cart->items->count();

        // Calculate grand total
        $this->cart->grand_total = $this->cart->sub_total + $this->cart->tax_total - $this->cart->discount_amount;
        $this->cart->base_grand_total = $this->cart->base_sub_total + $this->cart->base_tax_total - $this->cart->base_discount_amount;

        // Add shipping costs to grand total
        if ($shipping = $this->cart->selected_shipping_rate) {
            $this->cart->tax_total += $shipping->tax_amount;
            $this->cart->base_tax_total += $shipping->base_tax_amount;

            $this->cart->shipping_amount = $shipping->price;
            $this->cart->base_shipping_amount = $shipping->base_price;

            $this->cart->shipping_amount_incl_tax = $shipping->price_incl_tax;
            $this->cart->base_shipping_amount_incl_tax = $shipping->base_price_incl_tax;

            $this->cart->grand_total += $shipping->tax_amount + $shipping->price - $shipping->discount_amount;
            $this->cart->base_grand_total += $shipping->base_tax_amount + $shipping->base_price - $shipping->base_discount_amount;

            $this->cart->discount_amount += $shipping->discount_amount;
            $this->cart->base_discount_amount += $shipping->base_discount_amount;
        }

        // Round amounts
        $this->cart->discount_amount = round($this->cart->discount_amount, 2);
        $this->cart->base_discount_amount = round($this->cart->base_discount_amount, 2);
        $this->cart->sub_total = round($this->cart->sub_total, 2);
        $this->cart->base_sub_total = round($this->cart->base_sub_total, 2);
        $this->cart->sub_total_incl_tax = round($this->cart->sub_total_incl_tax, 2);
        $this->cart->base_sub_total_incl_tax = round($this->cart->base_sub_total_incl_tax, 2);
        $this->cart->grand_total = round($this->cart->grand_total, 2);
        $this->cart->base_grand_total = round($this->cart->base_grand_total, 2);

        // Assign cart currency
        $this->cart->cart_currency_code = core()->getCurrentCurrencyCode();

        $this->cart->save();

        Event::dispatch('checkout.cart.collect.totals.after', $this->cart);

        return $this;
    }

    /**
     * Update cart items information.
     */
    public function updateItems(array $data): bool|\Exception
    {
        foreach ($data['qty'] as $itemId => $quantity) {
            $item = $this->cartItemRepository->find($itemId);


            if (! $item) {
                continue;
            }

            if (! $item->product->status) {
                throw new \Exception(__('shop::app.checkout.cart.item.inactive'));
            }

            if ($quantity <= 0) {
                $this->removeItem($itemId);

                throw new \Exception(__('shop::app.checkout.cart.illegal'));
            }

            $item->quantity = $quantity;

            if (! $this->isItemHaveQuantity($item)) {
                throw new \Exception(__('shop::app.checkout.cart.inventory-warning'));
            }

            Event::dispatch('checkout.cart.update.before', $item);
            $wholesales = $this->productRepository->find($item->product_id)->first()->wholesales;
            $wholesale = $this->getBestWholesalePromotion($item, $wholesales);

            $itemFullPrintPrice = 0;

            if ($item->print_name != null) {
                $manipulationPrice = ($item->print_manipulation_cost / $quantity);
                $itemFullPrintPrice = round(($quantity * floatval($item->print_single_price)) + floatval($item->print_setup) + (floatval($item->additional['print-manipulation-single-price']) * $quantity), 2);
            }
            if($wholesale == null) {
                if ($item->print_name != null) {
                    $this->cartItemRepository->update([
                        'quantity'                  => $quantity,
                        'total'                     => $total = core()->convertPrice(($item->price_incl_tax * $item->quantity)),
                        'total_incl_tax'            => $total,
                        'base_total'                => $item->price_incl_tax * $quantity,
                        'base_total_incl_tax'       => $item->base_price_incl_tax * $quantity,
                        'total_weight'              => $item->weight * $quantity,
                        'base_total_weight'         => $item->weight * $quantity,
                        'discount_amount'           => '0',
                        'print_price'               => "$itemFullPrintPrice",
                        'print_single_price'        => $item->additional['technique-single-price'],
                        'print_setup'               => $item->additional['setup-price'],
                        'print_manipulation_cost'   => "$manipulationPrice",
                        'print_manipulation_single_price' => $item->additional['print-manipulation-single-price']
                    ], $itemId);
                } else {
                    $this->cartItemRepository->update([
                        'quantity'                  => $quantity,
                        'total'                     => $total = core()->convertPrice(($item->price_incl_tax * $item->quantity)),
                        'total_incl_tax'            => $total,
                        'base_total'                => $item->price_incl_tax * $quantity,
                        'base_total_incl_tax'       => $item->base_price_incl_tax * $quantity,
                        'total_weight'              => $item->weight * $quantity,
                        'base_total_weight'         => $item->weight * $quantity,
                        'discount_amount'           => '0',
                        'print_price'               => "$itemFullPrintPrice",

                    ], $itemId);
                }

                $this->flag = 1;

                Event::dispatch('checkout.cart.update.after', $item);

                $this->collectTotals();
                $this->flag = 0;

                return true;
            } else {
                if ($wholesale->batch_amount > $quantity) {
                    $product = $this->productRepository->find($item->product_id);
                    if ($item->print_name != null) {

                        $item = $this->cartItemRepository->update([
                            'quantity'            => $quantity,
                            'price'               => $product->price,
                            'price_incl_tax'      => $product->price,
                            'base_price'          => $product->base_price,
                            'base_price_incl_tax' => $product->base_price,
                            'total'               => $total = core()->convertPrice($item->base_price * $item->quantity),
                            'total_incl_tax'      => $total,
                            'base_total'          => ($baseTotal = $item->base_price * $item->quantity),
                            'base_total_incl_tax' => $item->baseTotal,
                            'discount_amount' => '0',
                            'total_weight'        => $item->weight * $quantity,
                            'base_total_weight'   => $item->weight * $quantity,
                            'print_price'         => "$itemFullPrintPrice",
                            'print_single_price'  => $item->additional['print-single-price'],
                            'print_setup'         => $item->additional['print-setup'],
                            'print_manipulation_cost'  => "$manipulationPrice",

                        ], $item->id);
                    } else {
                        $item = $this->cartItemRepository->update([
                            'quantity'            => $quantity,
                            'price'               => $product->price,
                            'price_incl_tax'      => $product->price,
                            'base_price'          => $product->base_price,
                            'base_price_incl_tax' => $product->base_price,
                            'total'               => $total = core()->convertPrice($item->base_price * $item->quantity),
                            'total_incl_tax'      => $total,
                            'base_total'          => ($baseTotal = $item->base_price * $item->quantity),
                            'base_total_incl_tax' => $item->baseTotal,
                            'discount_amount' => '0',
                            'total_weight'        => $item->weight * $quantity,
                            'base_total_weight'   => $item->weight * $quantity,
                        ], $item->id);
                    }
                    Event::dispatch('checkout.cart.update.after', $item);

                    $this->collectTotals();

                    return true;

                } else {
                    $itemData  = $this->transformCartItemByWholesale($item, $wholesale);

                    $this->cartItemRepository->update([
                        'quantity'            => $quantity,
                        'total'               => $total = core()->convertPrice($itemData->price_incl_tax * $quantity),
                        'total_incl_tax'      => $total,
                        'base_total'          => $itemData->price_incl_tax * $quantity,
                        'base_total_incl_tax' => $itemData->base_price_incl_tax * $quantity,
                        'total_weight'        => $itemData->weight * $quantity,
                        'base_total_weight'   => $itemData->weight * $quantity,
                        'print_price'         => "$itemFullPrintPrice",
                        'print_single_price'  => "$item->print_single_price",
                        'print_setup'         => "$item->print_setup",
                        'print_manipulation'  => "$item->print_manipulation_cost",
                    ], $itemId);

                    Event::dispatch('checkout.cart.update.after', $item);
                }

                $this->collectTotals();

                return true;
            }
        }
    }

    public function getPrintPrice($item)
    {
        return $item->additional;
    }


    /**
     * To validate if the product information is changed by admin and the items have been added to the cart before it.
     */
    public function validateItems(): bool
    {
        if (! $this->cart) {
            return false;
        }

        if (! $this->cart->items->count()) {
            $this->removeCart($this->cart);

            return false;
        }

        $isInvalid = false;

        foreach ($this->cart->items as $key => $item) {
            $validationResult = $item->getTypeInstance()->validateCartItem($item);

            if ($validationResult->isItemInactive()) {
                $this->removeItem($item->id);

                $isInvalid = true;

                session()->flash('info', __('shop::app.checkout.cart.inactive'));
            } else {
                if (Tax::isInclusiveTaxProductPrices()) {
                    $itemBasePrice = $item->base_price_incl_tax;
                } else {
                    $itemBasePrice = $item->base_price;
                }

                $basePrice = ! is_null($item->custom_price) ? $item->custom_price : $itemBasePrice;

                $price = core()->convertPrice($basePrice);

                /**
                 * Reset the item price every time to initial price if the inclusive price is enabled.
                 * Update the item price if exchange rates changes with exclusive price is enabled.
                 */
                if ($price != $item->price) {

                    $wholesales = $this->productRepository->find($item->product_id)->first()->wholesales;
                    $wholesale = $this->getBestWholesalePromotion($item, $wholesales);
                    $item  = $this->transformCartItemByWholesale($item, $wholesale);

                    $item = $this->cartItemRepository->update([
                        'price'               => $item->price,
                        'price_incl_tax'      => $item->price + $item->tax_amount,
                        'base_price'          => $item->base_price ,
                        'base_price_incl_tax' => $item->base_price + $item->base_tax_amount,
                        'total'               => $total = core()->convertPrice($item->base_price * $item->quantity),
                        'total_incl_tax'      => $total,
                        'base_total'          => ($baseTotal = $item->base_price * $item->quantity),
                        'base_total_incl_tax' => $item->base_total,
                    ], $item->id);

                    $this->cart->items->put($key, $item);
                }
            }

            $isInvalid |= $validationResult->isCartInvalid();
        }

        return ! $isInvalid;
    }

    /**
     * Calculates cart items tax.
     */
    public function calculateItemsTax(): void
    {
        if (! $this->cart) {
            return;
        }

        Event::dispatch('checkout.cart.calculate.items.tax.before', $this->cart);

        $taxCategories = [];

        foreach ($this->cart->items as $key => $item) {
            $taxCategoryId = $item->tax_category_id;

            if (empty($taxCategoryId)) {
                $taxCategoryId = $item->product->tax_category_id;
            }

            if (empty($taxCategoryId)) {
                $taxCategoryId = core()->getConfigData('sales.taxes.categories.product');
            }

            if (empty($taxCategoryId)) {
                continue;
            }

            if (! isset($taxCategories[$taxCategoryId])) {
                $taxCategories[$taxCategoryId] = $this->taxCategoryRepository->find($taxCategoryId);
            }

            if (! $taxCategories[$taxCategoryId]) {
                continue;
            }

            $calculationBasedOn = core()->getConfigData('sales.taxes.calculation.based_on');

            $address = null;

            if ($calculationBasedOn == self::TAX_CALCULATION_BASED_ON_SHIPPING_ORIGIN) {
                $address = Tax::getShippingOriginAddress();
            } elseif ($calculationBasedOn == self::TAX_CALCULATION_BASED_ON_SHIPPING_ADDRESS) {
                if ($item->getTypeInstance()->isStockable()) {
                    $address = $this->cart->shipping_address;
                } else {
                    $address = $this->cart->billing_address;
                }
            } elseif ($calculationBasedOn == self::TAX_CALCULATION_BASED_ON_BILLING_ADDRESS) {
                $address = $this->cart->billing_address;
            }

            if ($address === null && $this->cart->customer) {
                $address = $this->cart->customer->addresses()
                    ->where('default_address', 1)->first();
            }

            if ($address === null) {
                $address = Tax::getDefaultAddress();
            }

            $item->applied_tax_rate = null;

            $item->tax_percent = $item->tax_amount = $item->base_tax_amount = 0;

            Tax::isTaxApplicableInCurrentAddress($taxCategories[$taxCategoryId], $address, function ($rate) use ($item, $taxCategoryId) {
                $item->applied_tax_rate = $rate->identifier;

                $item->tax_category_id = $taxCategoryId;

                $item->tax_percent = $rate->tax_rate;

                if (Tax::isInclusiveTaxProductPrices()) {
                    $item->tax_amount = round(($item->total_incl_tax * $rate->tax_rate) / (100 + $rate->tax_rate), 4);

                    $item->base_tax_amount = round(($item->base_total_incl_tax * $rate->tax_rate) / (100 + $rate->tax_rate), 4);

                    $item->total = $item->total_incl_tax - $item->tax_amount;

                    $item->base_total = $item->base_total_incl_tax - $item->base_tax_amount;

                    $item->price = $item->total / $item->quantity;

                    $item->base_price = $item->base_total / $item->quantity;
                } else {

                    if (isset($item->print_price) && $item->print_price != '') {
                        $item->tax_amount = round((($item->total + $item->print_price) * $rate->tax_rate) / 100, 4);
                    } else {
                        $item->tax_amount = round(($item->total * $rate->tax_rate) / 100, 4);
                    }

                    $item->base_tax_amount = round(($item->base_total * $rate->tax_rate) / 100, 4);

                    $item->total_incl_tax = $item->total + $item->tax_amount;

                    $item->base_total_incl_tax = $item->base_total + $item->base_tax_amount;

                    $item->price_incl_tax = $item->price + ($item->tax_amount / $item->quantity);

                    $item->base_price_incl_tax = $item->base_price + ($item->base_tax_amount / $item->quantity);
                }
            });

            if (empty($item->applied_tax_rate)) {
                $item->price_incl_tax = $item->price;
                $item->base_price_incl_tax = $item->base_price;

                $item->total_incl_tax = $item->total;
                $item->base_total_incl_tax = $item->base_total;
            }

            $item->save();

            $this->cart->items->put($key, $item);
        }

        Event::dispatch('checkout.cart.calculate.items.tax.after', $this->cart);
    }

    /**
     * Calculates cart shipping tax.
     */
    public function calculateShippingTax(): void
    {
        if (! $this->cart) {
            return;
        }

        $shippingRate = $this->cart->selected_shipping_rate;

        if (! $shippingRate) {
            return;
        }

        if (! $taxCategoryId = core()->getConfigData('sales.taxes.categories.shipping')) {
            return;
        }

        $taxCategory = $this->taxCategoryRepository->find($taxCategoryId);

        $calculationBasedOn = core()->getConfigData('sales.taxes.calculation.based_on');

        $address = null;

        if ($calculationBasedOn == self::TAX_CALCULATION_BASED_ON_SHIPPING_ORIGIN) {
            $address = Tax::getShippingOriginAddress();
        } elseif (
            $this->cart->haveStockableItems()
            && $calculationBasedOn == self::TAX_CALCULATION_BASED_ON_SHIPPING_ADDRESS
        ) {
            $address = $this->cart->shipping_address;
        } elseif ($calculationBasedOn == self::TAX_CALCULATION_BASED_ON_BILLING_ADDRESS) {
            $address = $this->cart->billing_address;
        }

        if ($address === null && $this->cart->customer) {
            $address = $this->cart->customer->addresses()
                ->where('default_address', 1)->first();
        }

        if ($address === null) {
            $address = Tax::getDefaultAddress();
        }

        Event::dispatch('checkout.cart.calculate.shipping.tax.before', $this->cart);

        Tax::isTaxApplicableInCurrentAddress($taxCategory, $address, function ($rate) use ($shippingRate) {
            $shippingRate->applied_tax_rate = $rate->identifier;

            $shippingRate->tax_percent = $rate->tax_rate;

            if (Tax::isInclusiveTaxShippingPrices()) {
                $shippingRate->tax_amount = round(($shippingRate->price_incl_tax * $rate->tax_rate) / (100 + $rate->tax_rate), 4);

                $shippingRate->base_tax_amount = round(($shippingRate->base_price_incl_tax * $rate->tax_rate) / (100 + $rate->tax_rate), 4);

                $shippingRate->price = $shippingRate->price_incl_tax - $shippingRate->tax_amount;

                $shippingRate->base_price = $shippingRate->base_price_incl_tax - $shippingRate->base_tax_amount;
            } else {
                $shippingRate->tax_amount = round(($shippingRate->price * $rate->tax_rate) / 100, 4);

                $shippingRate->base_tax_amount = round(($shippingRate->base_price * $rate->tax_rate) / 100, 4);

                $shippingRate->price_incl_tax = $shippingRate->price + $shippingRate->tax_amount;

                $shippingRate->base_price_incl_tax = $shippingRate->base_price + $shippingRate->base_tax_amount;
            }
        });

        if (empty($shippingRate->applied_tax_rate)) {
            $shippingRate->price_incl_tax = $shippingRate->price;

            $shippingRate->base_price_incl_tax = $shippingRate->base_price;
        }

        $shippingRate->save();

        Event::dispatch('checkout.cart.calculate.shipping.tax.after', $this->cart);
    }
}
