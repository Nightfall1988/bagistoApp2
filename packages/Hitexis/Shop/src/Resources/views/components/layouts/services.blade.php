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
    <div>
        <p name='clientList'>@lang('shop::app.products.view.our-clients')</p>
    </div>
    <div class="container mt-20 px-4 max-lg:px-2 max-sm:px-1">
        <div class="flex justify-center gap-6 max-lg:flex-wrap">
            @foreach ($clients as $client)
            <div class="flex items-center gap-5 bg-white">
                <!-- Service Title -->
                <p class="font-dmserif text-base font-medium">{{$client->name}}</p>

                <!-- Service Description -->
                <img src="{{ route('client.logo', basename($client->logo_path)) }}" alt="">
            </div>
            @endforeach
        </div>
    </div>
@endif

{!! view_render_event('bagisto.shop.layout.features.after') !!}
