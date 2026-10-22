<?php

declare(strict_types=1);

namespace App\Domain\Orders\Queries;

use App\Models\InvoiceSetting;

final class GetInvoiceBrandingQuery
{
    /**
     * @return array{
     *     company_name: string,
     *     company_tagline: string,
     *     logo_url: string|null,
     *     header_start_color: string,
     *     header_end_color: string,
     *     footer_color: string,
     *     support_email: string|null,
     *     footer_credit: string
     * }
     */
    public function execute(): array
    {
        $setting =
            InvoiceSetting::current();

        return [
            'company_name' => $setting->company_name,

            'company_tagline' => $setting->company_tagline,

            'logo_url' => $setting->logo_path !== null
                ? asset(
                    'storage/'.ltrim(
                        $setting->logo_path,
                        '/',
                    ),
                )
                : null,

            'header_start_color' => $setting->header_start_color,

            'header_end_color' => $setting->header_end_color,

            'footer_color' => $setting->footer_color,

            'support_email' => $setting->support_email,

            'footer_credit' => $setting->footer_credit,
        ];
    }
}
