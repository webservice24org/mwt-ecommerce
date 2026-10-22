<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>New order</title>
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
                                New order
                            </p>

                            <h1 style="
                                    margin: 8px 0 0;
                                    font-size: 26px;
                                ">
                                {{ $order->orderNumber }}
                            </h1>

                            <p style="
                                    margin: 16px 0 0;
                                    color: #525252;
                                    line-height: 1.7;
                                ">
                                {{ $order->customerName }}
                                placed a new order for
                                <strong>
                                    {{ $order->currencyCode }}
                                    {{ number_format($order->grandTotal / 100, 2) }}
                                </strong>.
                            </p>

                            <p style="margin: 24px 0 0;">
                                <a href="{{ $adminOrderUrl }}" style="
                                        display: inline-block;
                                        padding: 12px 18px;
                                        border-radius: 8px;
                                        background: #171717;
                                        color: #ffffff;
                                        font-size: 14px;
                                        font-weight: 700;
                                        text-decoration: none;
                                    ">
                                    View order
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>