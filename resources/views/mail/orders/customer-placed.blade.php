<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order received</title>
</head>

<body style="
        margin: 0;
        padding: 0;
        background: #f5f5f5;
        font-family: Arial, Helvetica, sans-serif;
        color: #171717;
    ">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="
                        max-width: 640px;
                        background: #ffffff;
                        border: 1px solid #e5e5e5;
                        border-radius: 14px;
                    ">
                    <tr>
                        <td style="padding: 32px;">
                            <p style="
                                    margin: 0;
                                    color: #737373;
                                    font-size: 13px;
                                    font-weight: 700;
                                    text-transform: uppercase;
                                ">
                                Order received
                            </p>

                            <h1 style="
                                    margin: 8px 0 0;
                                    font-size: 26px;
                                    line-height: 1.3;
                                ">
                                Thank you for your order
                            </h1>

                            <p style="
                                    margin: 16px 0 0;
                                    color: #525252;
                                    font-size: 15px;
                                    line-height: 1.7;
                                ">
                                Hi {{ $order->customerName }},
                                we have received your order
                                <strong>{{ $order->orderNumber }}</strong>.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="
                                    margin-top: 24px;
                                    border-collapse: collapse;
                                ">
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td style="
                                                    padding: 12px 0;
                                                    border-bottom: 1px solid #eeeeee;
                                                ">
                                            <strong>
                                                {{ $item['product_name'] }}
                                            </strong>

                                            @if ($item['variant_description'])
                                                <div style="
                                                                margin-top: 4px;
                                                                color: #737373;
                                                                font-size: 13px;
                                                            ">
                                                    {{ $item['variant_description'] }}
                                                </div>
                                            @endif

                                            <div style="
                                                        margin-top: 4px;
                                                        color: #737373;
                                                        font-size: 13px;
                                                    ">
                                                Qty: {{ $item['quantity'] }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="margin-top: 24px;">
                                <tr>
                                    <td style="
                                            color: #525252;
                                            font-size: 14px;
                                        ">
                                        Total
                                    </td>

                                    <td align="right" style="
                                            font-size: 18px;
                                            font-weight: 700;
                                        ">
                                        {{ $order->currencyCode }}
                                        {{ number_format($order->grandTotal / 100, 2) }}
                                    </td>
                                </tr>
                            </table>

                            @if ($order->invoiceUrl)
                                <p style="margin: 24px 0 0;">
                                    <a href="{{ $order->invoiceUrl }}" style="
                                                display: inline-block;
                                                padding: 12px 18px;
                                                border-radius: 8px;
                                                background: #171717;
                                                color: #ffffff;
                                                font-size: 14px;
                                                font-weight: 700;
                                                text-decoration: none;
                                            ">
                                        View invoice
                                    </a>
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>