@inject ('reviewHelper', 'Webkul\Product\Helpers\Review')
@inject ('productViewHelper', 'Webkul\Product\Helpers\View')
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>

@php
    $avgRatings = $reviewHelper->getAverageRating($product);
    $percentageRatings = $reviewHelper->getPercentageRating($product);
    $customAttributeValues = $productViewHelper->getAdditionalData($product);

    $attributeData = collect($customAttributeValues)->filter(fn ($item) => ! empty($item['value']));
    
    $properCustomAttributes = ['sku', 'product_number', 'material', 'dimensions', 'height', 'width', 'weight'];
    $properData = [];

    foreach ($customAttributeValues as $attribute) {
        if (in_array($attribute['code'], $properCustomAttributes )) {
            $properData[] = $attribute;
        }
    }
    $customAttributeValues = $properData;

@endphp
<script>
    // Define the EventBus globally
    const EventBus = new Vue();
    window.EventBus = EventBus;
</script>
<!-- SEO Meta Content -->
@push('meta')
    <meta name="description" content="{{ trim($product->meta_description) != "" ? $product->meta_description : \Illuminate\Support\Str::limit(strip_tags($product->description), 120, '') }}"/>
    <meta name="keywords" content="{{ $product->meta_keywords }}"/>
    @if (core()->getConfigData('catalog.rich_snippets.products.enable'))
        <script type="application/ld+json">
            {!! app('Webkul\Product\Helpers\SEO')->getProductJsonLd($product) !!}
        </script>
    @endif

    <?php $productBaseImage = product_image()->getProductBaseImage($product); ?>

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $product->name }}" />
    <meta name="twitter:description" content="{!! htmlspecialchars(trim(strip_tags($product->description))) !!}" />
    <meta name="twitter:image" content="{{ $productBaseImage['medium_image_url'] }}" />
    <meta property="og:type" content="og:product" />
    <meta property="og:title" content="{{ $product->name }}" />
    <meta property="og:image" content="{{ $productBaseImage['medium_image_url'] }}" />
    <meta property="og:description" content="{!! htmlspecialchars(trim(strip_tags($product->description))) !!}" />
    <meta property="og:url" content="{{ route('shop.product_or_category.index', $product->url_key) }}" />
@endpush

<!-- Page Layout -->
<x-hitexis-shop::layouts>
    <div>
        <!-- Page Title -->
        <x-slot:title>
            {{ trim($product->meta_title) != "" ? $product->meta_title : $product->name }}
        </x-slot:title>
        {!! view_render_event('bagisto.shop.products.view.before', ['product' => $product]) !!}

        <!-- Breadcrumbs -->
        <div class="flex justify-center max-lg:hidden">
            <x-hitexis-shop::breadcrumbs
                name="product"
                :entity="$product"
            />
        </div>

        <!-- Product Information Vue Component -->
        <v-product>
            <x-shop::shimmer.products.view />
        </v-product>
    </div>

    {!! view_render_event('bagisto.shop.products.view.after', ['product' => $product]) !!}

    @pushOnce('scripts')
        <script type="text/x-template" id="v-product-template">
            <x-shop::form v-slot="{ meta, errors, handleSubmit }" as="div">
                <form ref="formData" @submit="handleSubmit($event, addToCart)">
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="is_buy_now" v-model="is_buy_now">

                    <div class="container px-[60px] max-1180:px-0">
                        <div class="mt-12 flex gap-9 max-1180:flex-wrap max-lg:mt-0 max-sm:gap-y-6">
                            <!-- Gallery Blade Inclusion -->
                            @include('hitexis-shop::products.view.gallery')

                            <!-- Product Details -->
                            <div class="relative max-w-[590px] max-1180:w-full max-1180:max-w-full max-1180:px-5">
                                <!-- Product Name -->
                                {!! view_render_event('bagisto.shop.products.name.before', ['product' => $product]) !!}
                                <div class="flex justify-between gap-4">
                                    <h1 class="text-3xl font-medium max-sm:text-xl text-mineShaft">
                                        {{ $product->name }}
                                    </h1>

                                    @if (core()->getConfigData('general.content.shop.wishlist_option'))
                                        <div class="flex max-h-[46px] min-h-[46px] min-w-[46px] cursor-pointer items-center justify-center rounded-full border border-black bg-white text-2xl transition-all hover:opacity-[0.8]"
                                             role="button"
                                             aria-label="@lang('shop::app.products.view.add-to-wishlist')"
                                             tabindex="0"
                                             :class="isWishlist ? 'icon-heart-fill' : 'icon-heart'"
                                             @click="addToWishlist"
                                        ></div>
                                    @endif
                                </div>
                                {!! view_render_event('bagisto.shop.products.name.after', ['product' => $product]) !!}

                                <!-- Ratings -->
                                {!! view_render_event('bagisto.shop.products.rating.before', ['product' => $product]) !!}
                                @if ($totalRatings = $reviewHelper->getTotalRating($product))
                                    <div class="mt-4 w-max cursor-pointer" role="button" tabindex="0" @click="scrollToReview">
                                        <x-shop::products.ratings
                                            class="transition-all hover:border-gray-400"
                                            :average="$avgRatings"
                                            :total="$totalRatings"
                                        />
                                    </div>
                                @endif
                                {!! view_render_event('bagisto.shop.products.rating.after', ['product' => $product]) !!}

                                <!-- Pricing -->
                                {!! view_render_event('bagisto.shop.products.price.before', ['product' => $product]) !!}
                                <p class="mt-5 flex items-center gap-2.5 text-2xl !font-medium max-sm:mt-4 max-sm:text-lg">
                                    {!! $product->getTypeInstance()->getPriceHtml() !!}
                                </p>
                                &nbsp;
                                <p class="text-sm text-zinc-500 max-sm:mt-4 max-xs:text-xs">
                                    <i>@lang('shop::app.products.view.price-no-tax')</i>
                                </p>
                                @if (\Webkul\Tax\Facades\Tax::isInclusiveTaxProductPrices())
                                    <span class="text-sm font-normal text-zinc-500">@lang('shop::app.products.view.tax-inclusive')</span>
                                @endif

                                @if (count($product->getTypeInstance()->getCustomerGroupPricingOffers()))
                                    <div class="mt-2.5 grid gap-1.5">
                                        @foreach ($product->getTypeInstance()->getCustomerGroupPricingOffers() as $offer)
                                            <p class="text-zinc-500 [&>*]:text-black">{!! $offer !!}</p>
                                        @endforeach
                                    </div>
                                @endif
                                {!! view_render_event('bagisto.shop.products.price.after', ['product' => $product]) !!}

                                <!-- Short Description -->
                                {!! view_render_event('bagisto.shop.products.short_description.before', ['product' => $product]) !!}
                                <p class="mt-6 text-lg text-zinc-500 max-sm:mt-4 max-sm:text-sm">
                                    {!! $product->short_description !!}
                                </p>
                                {!! view_render_event('bagisto.shop.products.short_description.after', ['product' => $product]) !!}

                                @include('hitexis-shop::products.view.types.configurable')
                                @include('shop::products.view.types.grouped')
                                @include('shop::products.view.types.bundle')
                                @include('shop::products.view.types.downloadable')

                                <!-- Product Actions -->
                                <div class="mt-8 flex max-w-[470px] gap-4">
                                    {!! view_render_event('bagisto.shop.products.view.quantity.before', ['product' => $product]) !!}
                                    <div id="field-qty">
                                        @if ($product->getTypeInstance()->showQuantityBox())
                                            <x-shop::quantity-changer
                                                id="field-qty"
                                                name="quantity"
                                                value="1"
                                                class="gap-x-4 rounded-xl px-7 py-4"
                                            />
                                        @endif
                                    </div>
                                    {!! view_render_event('bagisto.shop.products.view.quantity.after', ['product' => $product]) !!}

                                    {!! view_render_event('bagisto.shop.products.view.add_to_cart.before', ['product' => $product]) !!}
                                    <x-shop::button
                                        type="submit"
                                        class="secondary-button w-full max-w-full"
                                        button-type="secondary-button"
                                        :loading="false"
                                        :title="trans('shop::app.products.view.add-to-cart')"
                                        :disabled="! $product->isSaleable(1)"
                                        ::loading="isStoring.addToCart"
                                    />
                                    {!! view_render_event('bagisto.shop.products.view.add_to_cart.after', ['product' => $product]) !!}
                                </div>

                                <!-- Additional Info -->
                                <div id="additional-info" class="mt-8">
                                    <div class="grid grid-cols-2 gap-4 text-lg text-zinc-500 max-1180:text-sm">
                                        @if($product->getAttribute('material'))
                                            <div class="flex items-center">
                                                <p class="text-base text-black font-medium">@lang('shop::app.products.view.material'):&nbsp</p>
                                                <p class="ml-2 text-base text-zinc-500">{{ $product->getAttribute('material') }}</p>
                                            </div>
                                        @endif

                                        @if($product->getAttribute('dimensions'))
                                            <div class="flex items-center">
                                                <p class="text-base text-black font-medium">@lang('shop::app.products.view.dimensions'):&nbsp</p>
                                                <p class="ml-2 text-base text-zinc-500">{{ $product->getAttribute('dimensions') }}</p>
                                            </div>
                                        @endif

                                        <div class="flex items-center">
                                            <p class="text-base text-black font-medium">SKU:&nbsp</p>
                                            <p class="ml-2 text-base text-zinc-500">{{ $product->sku }}</p>
                                        </div>

                                        <div class="flex items-center">
                                            <p class="text-base text-black font-medium">Quantity:&nbsp</p>
                                            <p><span id="quantity-display">{{ $quantities[$product->sku] ?? 0 }}</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @php
                        $productPrintDataCount = sizeof($product->productPrintData) > 0
                            ? sizeof($product->productPrintData)
                            : (isset($product->variants) && isset($product->variants[0]) && sizeof($product->variants[0]->productPrintData) > 0
                                ? sizeof($product->variants[0]->productPrintData)
                                : (isset($product->parent) && sizeof($product->parent->productPrintData) > 0
                                    ? sizeof($product->parent->productPrintData)
                                    : 0));
                    @endphp
                    
                    <!-- Print Techniques and LogoTron -->
                    @if ($productPrintDataCount > 0)
                        <div class="mt-8">
                            @include('hitexis-shop::components.printcalculator.printcalculator', ['product' => $product])
                        </div>
                    @endif

                        <div class="flex flex-column">
                            <div class="flex flex-row max-w-[700px] gap-4" style="margin-top: 2rem;">
                                @if (isset($product->supplier))
                                    <div class="flex flex-row max-w-[300px] gap-4" style="margin-top: 2rem;">
                                        <button data-tl-action="OpenEditor" id="create-print-motive"
                                                data-tl-sid="{{ $product->supplier->supplier_code }}"
                                                data-tl-spcode="{{ $product->sku }}"
                                                class="secondary-button w-full max-w-full" type="button">
                                            @lang('shop::app.products.view.create-print-motive')
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </form>
            </x-shop::form>
             <!-- Information Section -->
                <div class="1180:mt-20">
                    <x-shop::tabs
                        position="center"
                        ref="productTabs"
                    >
                        <!-- Description Tab -->
                        {!! view_render_event('bagisto.shop.products.view.description.before', ['product' => $product]) !!}

                        <x-shop::tabs.item
                            id="descritpion-tab"
                            class="container mt-[60px] !p-0 max-1180:hidden"
                            :title="trans('shop::app.products.view.description')"
                            :is-selected="true"
                        >
                            <div class="container mt-[60px] max-1180:px-5">
                                <p class="text-lg text-zinc-500 max-1180:text-sm">
                                    {!! $product->description !!}
                                </p>
                            </div>
                        </x-shop::tabs.item>

                        {!! view_render_event('bagisto.shop.products.view.description.after', ['product' => $product]) !!}

                        <!-- Additional Information Tab -->
                        @if(count($attributeData))
                            <x-shop::tabs.item
                                id="information-tab"
                                class="container mt-[60px] !p-0 max-1180:hidden"
                                :title="trans('shop::app.products.view.additional-information')"
                                :is-selected="false"
                            >
                                <div class="container mt-[60px] max-1180:px-5">
                                    <div class="mt-8 grid max-w-max grid-cols-[auto_1fr] gap-4">
                                        @foreach ($customAttributeValues as $customAttributeValue)
                                            @if (! empty($customAttributeValue['value']))
                                                <div class="grid">
                                                    <p class="text-base text-black">
                                                        {!! $customAttributeValue['label'] !!}
                                                    </p>
                                                </div>

                                                @if ($customAttributeValue['type'] == 'file')
                                                    <a 
                                                        href="{{ Storage::url($product[$customAttributeValue['code']]) }}" 
                                                        download="{{ $customAttributeValue['label'] }}"
                                                    >
                                                        <span class="icon-download text-2xl"></span>
                                                    </a>
                                                @elseif ($customAttributeValue['type'] == 'image')
                                                    <a 
                                                        href="{{ Storage::url($product[$customAttributeValue['code']]) }}" 
                                                        download="{{ $customAttributeValue['label'] }}"
                                                    >
                                                        <img 
                                                            class="h-5 min-h-5 w-5 min-w-5" 
                                                            src="{{ Storage::url($customAttributeValue['value']) }}" 
                                                        />
                                                    </a>
                                                @else
                                                    <div class="grid">
                                                        <p class="text-base text-zinc-500">
                                                            {!! $customAttributeValue['value'] !!}
                                                        </p>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </x-shop::tabs.item>
                        @endif

                        <!-- Reviews Tab -->
                        <x-shop::tabs.item
                            id="review-tab"
                            class="container mt-[60px] !p-0 max-1180:hidden"
                            :title="trans('shop::app.products.view.review')"
                            :is-selected="false"
                        >
                            @include('hitexis-shop::products.view.reviews')
                        </x-shop::tabs.item>
                    </x-shop::tabs>
                </div>

                <!-- Information Section -->
                <div class="container mt-10 !p-0 max-1180:px-5 1180:hidden">
                    <!-- Description Accordion -->
                    <x-shop::accordion :is-active="true">
                        <x-slot:header class="bg-gray-100">
                            <p class="text-base font-medium 1180:hidden">
                                @lang('shop::app.products.view.description')
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <div class="mb-5 text-lg text-zinc-500 max-1180:text-sm">
                                {!! $product->description !!}
                            </div>
                        </x-slot>
                    </x-shop::accordion>

                    <!-- Additional Information Accordion -->
                    @if (count($attributeData))
                        <x-shop::accordion class="bg-gray-100" :is-active="false">
                            <x-slot:header>
                                <p class="text-base font-medium 1180:hidden">
                                    @lang('shop::app.products.view.additional-information')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <div class="container mb-4 max-1180:px-5">
                                    <div class="grid max-w-max grid-cols-[auto_1fr] gap-4 text-lg text-zinc-500 max-1180:text-sm">
                                        @foreach ($customAttributeValues as $customAttributeValue)
                                            @if (! empty($customAttributeValue['value']))
                                                <div class="grid">
                                                    <p class="text-base text-black">
                                                        {{ $customAttributeValue['label'] }}
                                                    </p>
                                                </div>

                                                @if ($customAttributeValue['type'] == 'file')
                                                    <a
                                                        href="{{ Storage::url($product[$customAttributeValue['code']]) }}"
                                                        download="{{ $customAttributeValue['label'] }}"
                                                    >
                                                        <span class="icon-download text-2xl"></span>
                                                    </a>
                                                @elseif ($customAttributeValue['type'] == 'image')
                                                    <a
                                                        href="{{ Storage::url($product[$customAttributeValue['code']]) }}"
                                                        download="{{ $customAttributeValue['label'] }}"
                                                    >
                                                        <img 
                                                            class="h-5 min-h-5 w-5 min-w-5" 
                                                            src="{{ Storage::url($customAttributeValue['value']) }}"
                                                            alt="Product Image"
                                                        />
                                                    </a>
                                                @else
                                                    <div class="grid">
                                                        <p class="text-base text-zinc-500">
                                                            {{ $customAttributeValue['value'] ?? '-' }}
                                                        </p>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </x-slot>
                        </x-shop::accordion>
                    @endif

                    <!-- Reviews Accordion -->
                    <x-shop::accordion class="bg-gray-100" :is-active="false">
                        <x-slot:header id="review-accordian-button">
                            <p class="text-base font-medium 1180:hidden">
                                @lang('shop::app.products.view.review')
                            </p>
                        </x-slot>

                        <x-slot:content>
                            @include('hitexis-shop::products.view.reviews')
                        </x-slot>
                    </x-shop::accordion>
                </div>

                <!-- Featured Products -->
                <x-shop::products.carousel
                    :title="trans('shop::app.products.view.related-product-title')"
                    :src="route('shop.api.products.related.index', ['id' => $product->id])"
                />

                <!-- Upsell Products -->
                <x-shop::products.carousel
                    :title="trans('shop::app.products.view.up-sell-title')"
                    :src="route('shop.api.products.up-sell.index', ['id' => $product->id])"
                />
        </script>

        <script type="module">
            const quantities = @json($quantities);
            
            app.component('v-product', {
                template: '#v-product-template',
                data() {
                    return {
                        sku: '{{ $product->sku }}',
                        supplierCode: '{{ $product->supplier->supplier_code ?? "0" }}',
                        isWishlist: Boolean("{{ (boolean) auth()->guard()->user()?->wishlist_items->where('channel_id', core()->getCurrentChannel()->id)->where('product_id', $product->id)->count() }}"),
                        isCustomer: '{{ auth()->guard('customer')->check() }}',
                        is_buy_now: 0,
                        isStoring: {
                            addToCart: false,
                            buyNow: false,
                        },
                        quantity: quantities['{{ $product->sku }}'] || 0,
                    }
                },
                mounted() {
                    this.updateQuantity();
                    this.$emitter.on('configurable-variant-update-sku-event', (newSku) => {
                        this.sku = newSku.sku;
                        this.updateButtonSku(newSku);
                        this.updateQuantity();
                    });
                },
                methods: {
                    updateVariantColor(color) {
                        this.$refs.printCalculator.updateVariantColor(color);
                    },

                    updateQuantity() {                        
                        const quantityDisplay = document.getElementById('quantity-display');
                        this.quantity = quantities[this.sku] || 0; 
                        quantityDisplay.textContent = this.quantity;
                    },
                    updateButtonSku(sku) {
                        const button2 = document.getElementById('create-print-motive');
                        button2.setAttribute('data-tl-spcode', sku.sku);
                    },
                    addToCart(params) {
                        const operation = this.is_buy_now ? 'buyNow' : 'addToCart';
                        this.isStoring[operation] = true;
                        let formData = new FormData(this.$refs.formData);
                        this.$axios.post('{{ route("shop.api.checkout.cart.store") }}', formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                }
                            })
                            .then(response => {
                                if (response.data.message) {
                                    this.$emitter.emit('update-mini-cart', response.data.data);
                                    this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                                    if (response.data.redirect) {
                                        window.location.href= response.data.redirect;
                                    }
                                } else {
                                    this.$emitter.emit('add-flash', { type: 'warning', message: response.data.data.message });
                                }
                                this.isStoring[operation] = false;
                            })
                            .catch(error => {
                                this.isStoring[operation] = false;
                                this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.message });
                            });
                    },
                    addToWishlist() {
                        if (this.isCustomer) {
                            this.$axios.post('{{ route('shop.api.customers.account.wishlist.store') }}', {
                                    product_id: "{{ $product->id }}"
                                })
                                .then(response => {
                                    this.isWishlist = ! this.isWishlist;
                                    this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message });
                                })
                                .catch(error => {});
                        } else {
                            window.location.href = "{{ route('shop.customer.session.index')}}";
                        }
                    },
                    addToCompare(productId) {
                        if (this.isCustomer) {
                            this.$axios.post('{{ route("shop.api.compare.store") }}', {
                                    'product_id': productId
                                })
                                .then(response => {
                                    this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message });
                                })
                                .catch(error => {
                                    if ([400, 422].includes(error.response.status)) {
                                        this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.data.message });
                                        return;
                                    }
                                    this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message});
                                });
                            return;
                        }

                        let existingItems = this.getStorageValue(this.getCompareItemsStorageKey()) ?? [];
                        if (! existingItems.includes(productId)) {
                            existingItems.push(productId);
                            this.setStorageValue(this.getCompareItemsStorageKey(), existingItems);
                            this.$emitter.emit('add-flash', { type: 'success', message: "@lang('shop::app.products.view.add-to-compare')" });
                        } else {
                            this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('shop::app.products.view.already-in-compare')" });
                        }
                    },
                    scrollToReview() {
                        let accordianElement = document.querySelector('#review-accordian-button');
                        if (accordianElement) {
                            accordianElement.click();
                            accordianElement.scrollIntoView({ behavior: 'smooth' });
                        }
                        let tabElement = document.querySelector('#review-tab-button');
                        if (tabElement) {
                            tabElement.click();
                            tabElement.scrollIntoView({ behavior: 'smooth' });
                        }
                    }
                },
            });
        </script>
    @endPushOnce
</x-hitexis-shop::layouts>
