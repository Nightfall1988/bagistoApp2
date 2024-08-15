{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.before') !!}

<div class="flex min-h-[78px] w-full justify-between border border-b border-l-0 border-r-0 border-t-0 px-[60px] px-8">
    <!--
        This section will provide categories for the first, second, and third levels. If
        additional levels are required, users can customize them according to their needs.
    -->
    <!-- Left Navigation Section -->
    <div class="flex flex-row content-center flex-wrap justify-between w-full gap-8" style="align-content: center!important;">
        <div class="flex items-center gap-x-5 lg:gap-x-10 ">
            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.before') !!}

            <a
                href="{{ route('shop.home.index') }}"
                aria-label="@lang('shop::app.components.layouts.header.bagisto')"
            >
                <img
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                    width="131"
                    height="29"
                    alt="{{ config('app.name') }}"
                >
            </a>
            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.after') !!}
        </div>
        <!-- Right Navigation Section -->
        <div id='search-bar-form' class="flex flex-grow mt-auto mb-auto ml-4" >

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.before') !!}
            <!-- Search Bar Container -->
            <div class="relative w-full">
                <form
                    action="{{ route('shop.search.index') }}"
                    class="flex w-full items-center"
                    role="search"
                >
                    <label
                        for="organic-search"
                        class="sr-only"
                    >
                        @lang('shop::app.components.layouts.header.search')
                    </label>

                    <div class="icon-search pointer-events-none absolute top-2.5 flex items-center text-xl ltr:left-3 rtl:right-3"></div>

                    <input
                        type="text"
                        name="query"
                        value="{{ request('query') }}"
                        class="block w-full rounded-lg border border-transparent bg-zinc-100 px-11 py-3 text-xs font-medium text-gray-900 transition-all hover:border-gray-400 focus:border-gray-400"
                        placeholder="@lang('shop::app.components.layouts.header.search-text')"
                        aria-label="@lang('shop::app.components.layouts.header.search-text')"
                        aria-required="true"
                        required
                    >

                    <button
                        type="submit"
                        class="hidden"
                        aria-label="@lang('shop::app.components.layouts.header.submit')"
                    >
                    </button>

                    @if (core()->getConfigData('general.content.shop.image_search'))
                        @include('shop::search.images.index')
                    @endif
                </form>
            </div>
        </div>

    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.after') !!}

    <!-- Right Navigation Links -->
    <div style="align-content: center;">
        
        <div class="mt-1.5 flex gap-x-8 max-[1100px]:gap-x-6 max-lg:gap-x-8">
            <div>
                <a href="{{ route('shop.home.contact_us') }}">
                <img
                    src="{{ bagisto_asset('images/contact-logo.png') }}"
                    width="25"
                    height="25"
                >
                </a>
            </div>
            
            <!-- Currency Switcher -->
            <x-shop::dropdown>
                <x-slot:toggle>
                    <div class="flex cursor-pointer gap-2.5" role="button" tabindex="0" @click="currencyToggler = !currencyToggler">
                        <span>{{ core()->getCurrentCurrency()->symbol . ' ' . core()->getCurrentCurrencyCode() }}</span>
                        <span class="text-2xl" :class="{'icon-arrow-up': currencyToggler, 'icon-arrow-down': !currencyToggler}" role="presentation"></span>
                    </div>
                </x-slot>
                <x-slot:content class="!p-0">
                    <v-currency-switcher></v-currency-switcher>
                </x-slot>
            </x-shop::dropdown>

            <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                <x-slot:toggle>
                    <!-- Dropdown Toggler -->
                    <div
                        class="flex cursor-pointer items-center gap-2.5"
                        role="button"
                        tabindex="0"
                        @click="localeToggler = ! localeToggler"
                    >
                        <img
                            src="{{ ! empty(core()->getCurrentLocale()->logo_url)
                                    ? core()->getCurrentLocale()->logo_url
                                    : bagisto_asset('images/default-language.svg')
                                }}"
                            class="h-full"
                            alt="@lang('shop::app.components.layouts.header.desktop.top.default-locale')"
                            width="24"
                            height="16"
                        />
                        
                        <span>
                            {{ core()->getCurrentChannel()->locales()->orderBy('name')->where('code', app()->getLocale())->value('name') }}
                        </span>

                        <span
                            class="text-2xl"
                            :class="{'icon-arrow-up': localeToggler, 'icon-arrow-down': ! localeToggler}"
                            role="presentation"
                        ></span>
                    </div>
                </x-slot>
            
                <!-- Dropdown Content -->
                <x-slot:content class="!p-0">
                    <v-locale-switcher></v-locale-switcher>
                </x-slot>
            </x-shop::dropdown>

            <!-- Compare -->
            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.before') !!}
            @if(core()->getConfigData('general.content.shop.compare_option'))
                <a
                    href="{{ route('shop.compare.index') }}"
                    aria-label="@lang('shop::app.components.layouts.header.compare')"
                >
                    <span
                        class="icon-compare inline-block cursor-pointer text-2xl"
                        role="presentation"
                    ></span>
                </a>
            @endif
            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.after') !!}

            <!-- Mini Cart -->
            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.before') !!}
            @include('hitexis-shop::checkout.cart.mini-cart')
            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.after') !!}

            <!-- User Profile -->
            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.before') !!}
            <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                <x-slot:toggle>
                    <span
                        class="icon-users inline-block cursor-pointer text-2xl"
                        role="button"
                        aria-label="@lang('shop::app.components.layouts.header.profile')"
                        tabindex="0"
                    ></span>
                </x-slot>

                <!-- Guest Dropdown -->
                @guest('customer')
                    <x-slot:content>
                        <div class="grid gap-2.5">
                            <p class="font-dmserif text-xl">
                                @lang('shop::app.components.layouts.header.welcome-guest')
                            </p>

                            <p class="text-sm">
                                @lang('shop::app.components.layouts.header.dropdown-text')
                            </p>
                        </div>

                        <p class="py-2px mt-3 w-full border border-zinc-200"></p>

                        <div class="mt-6 flex gap-4">
                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.sign_in_button.before') !!}

                            <a
                                href="{{ route('shop.customer.session.create') }}"
                                class="primary-button m-0 mx-auto block w-max rounded-2xl px-7 text-center text-base ltr:ml-0 rtl:mr-0"
                            >
                                @lang('shop::app.components.layouts.header.sign-in')
                            </a>

                            <a
                                href="{{ route('shop.customers.register.index') }}"
                                class="secondary-button m-0 mx-auto block w-max rounded-2xl border-2 px-7 text-center text-base ltr:ml-0 rtl:mr-0"
                            >
                                @lang('shop::app.components.layouts.header.sign-up')
                            </a>

                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.sign_up_button.after') !!}
                        </div>
                    </x-slot>
                @endguest

                <!-- Customers Dropdown -->
                @auth('customer')
                    <x-slot:content class="!p-0">
                        <div class="grid gap-2.5 p-5 pb-0">
                            <p class="font-dmserif text-xl">
                                @lang('shop::app.components.layouts.header.welcome')
                                {{ auth()->guard('customer')->user()->first_name }}
                            </p>

                            <p class="text-sm">
                                @lang('shop::app.components.layouts.header.dropdown-text')
                            </p>
                        </div>

                        <p class="py-2px mt-3 w-full border border-zinc-200"></p>

                        <div class="mt-2.5 grid gap-1 pb-2.5">
                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile_dropdown.links.before') !!}

                            <a
                                class="cursor-pointer px-5 py-2 text-base hover:bg-gray-100"
                                href="{{ route('shop.customers.account.profile.index') }}"
                            >
                                @lang('shop::app.components.layouts.header.profile')
                            </a>

                            <a
                                class="cursor-pointer px-5 py-2 text-base hover:bg-gray-100"
                                href="{{ route('shop.customers.account.orders.index') }}"
                            >
                                @lang('shop::app.components.layouts.header.orders')
                            </a>

                            @if (core()->getConfigData('general.content.shop.wishlist_option'))
                                <a
                                    class="cursor-pointer px-5 py-2 text-base hover:bg-gray-100"
                                    href="{{ route('shop.customers.account.wishlist.index') }}"
                                >
                                    @lang('shop::app.components.layouts.header.wishlist')
                                </a>
                            @endif

                            <!--Customers logout-->
                            @auth('customer')
                                <x-shop::form
                                    method="DELETE"
                                    action="{{ route('shop.customer.session.destroy') }}"
                                    id="customerLogout"
                                />

                                <a
                                    class="cursor-pointer px-5 py-2 text-base hover:bg-gray-100"
                                    href="{{ route('shop.customer.session.destroy') }}"
                                    onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                                >
                                    @lang('shop::app.components.layouts.header.logout')
                                </a>
                            @endauth

                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile_dropdown.links.after') !!}
                        </div>
                    </x-slot>
                @endauth
            </x-shop::dropdown>
            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.after') !!}
        </div>
    </div>
</div>

</div>

@pushOnce('scripts')
<script
type="text/x-template"
id="v-currency-switcher-template"
>
<div class="mt-2.5 grid gap-1 pb-2.5">
    <span
        class="cursor-pointer px-5 py-2 text-base hover:bg-gray-100"
        v-for="currency in currencies"
        :class="{'bg-gray-100': currency.code == '{{ core()->getCurrentCurrencyCode() }}'}"
        @click="change(currency)"
    >
        @{{ currency.symbol + ' ' + currency.code }}
    </span>
</div>
</script>

<script
type="text/x-template"
id="v-locale-switcher-template"
>
<div class="mt-2.5 grid gap-1 pb-2.5">
    <span
        class="flex cursor-pointer items-center gap-2.5 px-5 py-2 text-base hover:bg-gray-100"
        :class="{'bg-gray-100': locale.code == '{{ app()->getLocale() }}'}"
        v-for="locale in locales"
        @click="change(locale)"                  
    >
        <img
            :src="locale.logo_url || '{{ bagisto_asset('images/default-language.svg') }}'"
            width="24"
            height="16"
        />

        @{{ locale.name }}
    </span>
</div>
</script>

    <script
        type="text/x-template"
        id="v-desktop-category-template"
    >
        <div
            class="flex items-center gap-5"
            v-if="isLoading"
        >
            <span
                class="shimmer h-6 w-20 rounded"
                role="presentation"
            ></span>

            <span
                class="shimmer h-6 w-20 rounded"
                role="presentation"
            ></span>

            <span
                class="shimmer h-6 w-20 rounded"
                role="presentation"
            ></span>
        </div>

        <div
            class="flex items-center"
            v-else
        >
            <div
                class="group relative flex h-[77px] items-center border-b-[4px] border-transparent hover:border-b-[4px] hover:border-navyBlue"
                v-for="category in categories"
            >
                <span>
                    <a
                        :href="category.url"
                        class="inline-block px-5 uppercase text-mineShaft"
                    >
                        @{{ category.name }}
                    </a>
                </span>

                <div
                    class="pointer-events-none absolute top-[78px] z-[1] max-h-[580px] w-max max-w-[1260px] translate-y-1 overflow-auto overflow-x-auto border border-b-0 border-l-0 border-r-0 border-t border-[#F3F3F3] bg-white p-9 opacity-0 shadow-[0_6px_6px_1px_rgba(0,0,0,.3)] transition duration-300 ease-out group-hover:pointer-events-auto group-hover:translate-y-0 group-hover:opacity-100 group-hover:duration-200 group-hover:ease-in ltr:-left-9 rtl:-right-9"
                    v-if="category.children.length"
                >
                    <div class="aligns flex gap-x-[70px]">
                        <div
                            class="grid w-full min-w-max max-w-[150px] flex-auto grid-cols-[1fr] content-start gap-5"
                            v-for="pairCategoryChildren in pairCategoryChildren(category)"
                        >
                            <template v-for="secondLevelCategory in pairCategoryChildren">
                                <p class="font-medium text-navyBlue">
                                    <a :href="secondLevelCategory.url">
                                        @{{ secondLevelCategory.name }}
                                    </a>
                                </p>

                                <ul
                                    class="grid grid-cols-[1fr] gap-3"
                                    v-if="secondLevelCategory.children.length"
                                >
                                    <li
                                        class="text-sm font-medium text-zinc-500"
                                        v-for="thirdLevelCategory in secondLevelCategory.children"
                                    >
                                        <a :href="thirdLevelCategory.url">
                                            @{{ thirdLevelCategory.name }}
                                        </a>
                                    </li>
                                </ul>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>


    <script type="module">
        app.component('v-desktop-category', {
            template: '#v-desktop-category-template',

            data() {
                return  {
                    isLoading: true,

                    categories: [],

                    localeToggler: '',

                    currencyToggler: '',
                }
            },

            mounted() {
                this.get();
            },

            methods: {
                get() {
                    this.$axios.get("{{ route('shop.api.categories.tree') }}")
                        .then(response => {
                            this.isLoading = false;
                            this.categories = response.data.data;
                        }).catch(error => {
                            console.log(error);
                        });
                },

                pairCategoryChildren(category) {
                    return category.children.reduce((result, value, index, array) => {
                        if (index % 2 === 0) {
                            result.push(array.slice(index, index + 2));
                        }

                        return result;
                    }, []);
                }
            },
        });

        app.component('v-currency-switcher', {
            template: '#v-currency-switcher-template',

            data() {
                return {
                    currencies: @json(core()->getCurrentChannel()->currencies),
                };
            },

            methods: {
                change(currency) {
                    let url = new URL(window.location.href);

                    url.searchParams.set('currency', currency.code);

                    window.location.href = url.href;
                }
            }
        });

        app.component('v-locale-switcher', {
            template: '#v-locale-switcher-template',

            data() {
                return {
                    locales: @json(core()->getCurrentChannel()->locales()->orderBy('name')->get()),
                };
            },

            methods: {
                change(locale) {
                    let url = new URL(window.location.href);

                    url.searchParams.set('locale', locale.code);

                    window.location.href = url.href;
                }
            }
        });
        
    </script>
@endPushOnce

{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.after') !!}
