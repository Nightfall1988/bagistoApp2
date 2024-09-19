@if ($prices['final']['price'] < $prices['regular']['price'])
    <p
        class="font-medium text-zinc-500 line-through"
        aria-label="{{ $prices['regular']['formatted_price'] }}"
    >
        {{ $prices['regular']['formatted_price'] }}
    </p>

    <p class="font-semibold">
        {{ $prices['final']['formatted_price'] }}
    </p>
@else
    <p class="font-semibold">
        {{ $prices['regular']['formatted_price'] }}
    </p>
@endif
&nbsp;
<p class="text-sm text-zinc-500 max-sm:mt-4 max-xs:text-xs">
    <i>@lang('shop::app.products.view.price-no-tax')</i>
</p>