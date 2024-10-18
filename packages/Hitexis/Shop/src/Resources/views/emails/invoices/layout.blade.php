<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    </head>

    <body style="font-family: inter;">
        <div style="max-width: 640px; margin-left: auto; margin-right: auto;">
            <div style="padding: 30px;">
                <!-- Email Header -->
                <div style="margin-bottom: 65px;">
                    <a href="{{ route('shop.home.index') }}">
                    @if (core()->getConfigData('sales.invoice_settings.invoice_slip_design.logo'))
                        <div class="image" style="display:inline-block; vertical-align: middle; padding-top:8px">
                            <img class="logo" style="height: 40px; width: 150px;" src="{{ Storage::url(core()->getConfigData('sales.invoice_settings.invoice_slip_design.logo')) }}" alt=""/>
                        </div>
                    @else
                        <div class="without_logo" style="display:inline-block; vertical-align: middle; padding-top:8px">
                        </div>
                    @endif
                    </a>
                </div>

                <!-- Email Content -->
                {{ $slot }}

                <!-- Email Footer -->
                <p style="font-size: 16px;color: #202B3C;line-height: 24px;">
                    @lang('shop::app.emails.thanks', [
                        'phone' => ' +37126383899',
                        'style' => 'color: #2969FF;'
                    ])
                </p>
            </div>
        </div>
    </body>
</html>
