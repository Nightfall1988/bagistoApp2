{!! view_render_event('bagisto.shop.layout.features.before') !!}

<!--
    The ThemeCustomizationRepository repository is injected directly here because there is no way
    to retrieve it from the view composer, as this is an anonymous component.
-->
@inject('themeCustomizationRepository', 'Webkul\Theme\Repositories\ThemeCustomizationRepository')
@inject('clientRepository', 'Hitexis\Product\Repositories\ClientRepository')
@php
    $clients = $clientRepository->all();
@endphp

<!-- Features -->
@if ($clients)
<!-- Thin horizontal line to separate the section -->
<div class="border-t border-gray-300 my-8"></div>

<div class="flex flex-col justify-center items-center">
    <div class="text-center mb-12">
        <p name='clientList' class="text-4xl font-bold text-navyBlue">@lang('shop::app.products.view.our-clients')</p>
    </div>
    <div class="container mt-10 max-lg:px-8 max-sm:mt-8">
        <div class="flex justify-center gap-10 max-lg:flex-wrap">
            @foreach ($clients as $client)
                <div class="flex flex-col items-center bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center justify-center w-[150px] h-[150px] bg-white p-2.5" role="presentation">
                        <img src="{{ bagisto_asset('images/client_logos/' . $client->logo_path) }}" alt="{{ $client->name }}" class="object-contain">
                    </div>
                    <div class="flex justify-center mt-4">
                        <!-- Client Name -->
                        <p class="font-dmserif text-2xl text-navyBlue">{{$client->name }}</p>
                        {{--
                        <!-- Service Description -->
                        <p class="mt-2.5 max-w-[217px] text-sm font-medium text-zinc-500">
                            {{$service['description']}}
                        </p> --}}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{!! view_render_event('bagisto.shop.layout.features.after') !!}
