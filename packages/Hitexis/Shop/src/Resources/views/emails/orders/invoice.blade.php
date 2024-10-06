@inject('Tax', 'Webkul\Tax\Tax')

@component('hitexis-shop::emails.layout')
    <div style="margin-bottom: 34px;">
        <span style="font-size: 22px;font-weight: 600;color: #121A26">
            @lang('shop::app.emails.orders.created.title')
        </span> <br>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            @lang('shop::app.emails.dear', ['customer_name' => $order->customer_full_name]),👋
        </p>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            {!! __('shop::app.emails.orders.created.greeting', [
                'order_id' => '<a href="' . route('shop.customers.account.orders.view', $order->id) . '" style="color: #2969FF;">#' . $order->increment_id . '</a>',
                'created_at' => core()->formatDate($order->created_at, 'Y-m-d H:i:s')
                ])
            !!}
        </p>
    </div>

    <div style="font-size: 20px;font-weight: 600;color: #121A26">
        @lang('shop::app.emails.orders.created.summary')
    </div>

    <div style="display: flex;flex-direction: row;margin-top: 20px;justify-content: space-between;margin-bottom: 40px;">
        @if ($order->shipping_address)
            <div style="line-height: 25px;">
                <div style="font-size: 16px;font-weight: 600;color: #121A26;">
                    @lang('shop::app.emails.orders.shipping-address')
                </div>

                <div style="font-size: 16px;font-weight: 400;color: #384860;margin-bottom: 40px;">
                    {{ $order->shipping_address->company_name ?? '' }}<br/>
                    {{ $order->shipping_address->name }}<br/>
                    {{ $order->shipping_address->address }}<br/>
                    {{ $order->shipping_address->postcode . " " . $order->shipping_address->city }}<br/>
                    {{ $order->shipping_address->state }}<br/>
                    ---<br/>
                    @lang('shop::app.emails.orders.contact') : {{ $order->billing_address->phone }}
                </div>

                <div style="font-size: 16px;font-weight: 600;color: #121A26;">
                    @lang('shop::app.emails.orders.shipping')
                </div>

                <div style="font-size: 16px;font-weight: 400;color: #384860;">
                    {{ explode(' - ', $order->shipping_title)[0] }}
                </div>
            </div>
        @endif

        @if ($order->billing_address)
            <div style="line-height: 25px;">
                <div style="font-size: 16px;font-weight: 600;color: #121A26;">
                    @lang('shop::app.emails.orders.billing-address')
                </div>

                <div style="font-size: 16px;font-weight: 400;color: #384860;margin-bottom: 40px;">
                    {{ $order->billing_address->company_name ?? '' }}<br/>

                    @if ($order->billing_address->registration_number)
                        @lang('shop::app.emails.orders.registration-nr') {{ $order->billing_address->registration_number }}<br/>
                    @endif

                    {{ $order->billing_address->name }}<br/>
                    {{ $order->billing_address->address }}<br/>
                    {{ $order->billing_address->postcode . " " . $order->billing_address->city }}<br/>
                    {{ $order->billing_address->state }}<br/>
                    ---<br/>
                    @lang('shop::app.emails.orders.contact') {{ $order->billing_address->phone }}
                </div>

                <div style="font-size: 16px;font-weight: 600;color: #121A26;">
                    @lang('shop::app.emails.orders.payment')
                </div>

                <div style="font-size: 16px;font-weight: 400;color: #384860;">
                    {{ core()->getConfigData('sales.payment_methods.' . $order->payment->method . '.title') }}
                </div>

                @php $additionalDetails = \Webkul\Payment\Payment::getAdditionalDetails($order->payment->method); @endphp

                @if (! empty($additionalDetails))
                    <div style="font-size: 16px; color: #384860;">
                        <div>{{ $additionalDetails['title'] }}</div>
                        <div>{{ $additionalDetails['value'] }}</div>
                    </div>
                @endif 
            </div>
        @endif
    </div>

    <div style="padding-bottom: 40px;border-bottom: 1px solid #CBD5E1;">
        <table style="overflow-x: auto; border-collapse: collapse; border-spacing: 0;width: 100%">
            <thead>
                <tr style="color: #121A26;border-top: 1px solid #CBD5E1;border-bottom: 1px solid #CBD5E1;">
                    @foreach (['sku', 'name', 'price', 'qty'] as $item)
                        <th style="text-align: left;padding: 15px">
                            @lang('shop::app.emails.orders.' . $item)
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody style="font-size: 16px;font-weight: 400;color: #384860;">
                @foreach ($order->items as $item)
                    {{-- Main product row --}}
                    <tr>
                        <td style="text-align: left;padding: 15px">{{ $item->sku }}</td>
                        <td style="text-align: left;padding: 15px">{{ $item->name }}</td>
                        <td style="text-align: left;padding: 15px">{{ core()->formatPrice(floatval(str_replace(',', '.', $item->price)), $order->order_currency_code) }}</td>
                        <td style="text-align: left;padding: 15px">{{ $item->qty_ordered }}</td>
                    </tr>

                    {{-- Print details row --}}
                    <tr>
                        <td colspan="4" style="padding: 0; border: none;">
                            <div style="padding: 10px; background-color: #F9F9F9; border-radius: 4px;">
                                <b>@lang('shop::app.emails.orders.print-name-position'):</b>
                                {{ rtrim($item->print_name ?? __('shop::app.products.view.calculator.no-technique'), ' -') }}<br/>
                                
                                @if (floatval($item->print_single_price) != 0)
                                    <b>@lang('shop::app.emails.orders.print-single-price'):</b> 
                                    {{ core()->formatPrice(floatval($item->print_single_price) * $item->qty_ordered, $order->order_currency_code) }}<br/>
                                
                                    <b>@lang('shop::app.emails.orders.print-setup'):</b> 
                                    {{ core()->formatPrice(floatval(str_replace(',', '.', $item->print_setup)), $order->order_currency_code) }}<br/>
                                
                                    <b>@lang('shop::app.emails.orders.print-manipulation'):</b> 
                                    {{ core()->formatPrice(floatval($item->print_manipulation_cost) * $item->qty_ordered, $order->order_currency_code) }}<br/>
                                
                                    <b>@lang('shop::app.emails.orders.print-price'):</b> 
                                    {{ core()->formatPrice(floatval($item->print_price), $order->order_currency_code) }}<br/>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="font-size: 16px;color: #384860;line-height: 30px;padding-top: 20px;padding-bottom: 20px;">
        <table style="width: 100%;">
            @if (!is_null($order->print_price) && $order->print_price != '0.00')
            <tr>
                <td style="text-align: left;">
                    @lang('shop::app.emails.orders.print-total')
                </td>
                <td style="text-align: right;">
                    {{ core()->formatPrice(floatval($order->print_price), $order->order_currency_code) }}
                </td>
            </tr>
            @endif

            <tr>
                <td style="text-align: left;">
                    @lang('shop::app.emails.orders.subtotal')
                </td>
                <td style="text-align: right;">
                    {{ core()->formatPrice($order->sub_total, $order->order_currency_code) }}
                </td>
            </tr>

            @if ($order->shipping_address)
            <tr>
                <td style="text-align: left;">
                    @lang('shop::app.emails.orders.shipping-handling')
                </td>
                <td style="text-align: right;">
                    {{ core()->formatPrice($order->shipping_amount, $order->order_currency_code) }}
                </td>
            </tr>
            @endif

            @foreach (Webkul\Tax\Helpers\Tax::getTaxRatesWithAmount($order, false) as $taxRate => $taxAmount)
            <tr>
                <td style="text-align: left;">
                    @lang('shop::app.emails.orders.tax') {{ $taxRate }} %
                </td>
                <td style="text-align: right;">
                    {{ core()->formatPrice($taxAmount, $order->order_currency_code) }}
                </td>
            </tr>
            @endforeach

            @if ($order->discount_amount > 0)
            <tr>
                <td style="text-align: left;">
                    @lang('shop::app.emails.orders.discount')
                </td>
                <td style="text-align: right;">
                    {{ core()->formatPrice($order->discount_amount, $order->order_currency_code) }}
                </td>
            </tr>
            @endif

            <tr style="font-weight: bold;">
                <td style="text-align: left;">
                    @lang('shop::app.emails.orders.grand-total')
                </td>
                <td style="text-align: right;">
                    {{ core()->formatPrice($order->grand_total, $order->order_currency_code) }}
                </td>
            </tr>
        </table>
    </div>
@endcomponent
