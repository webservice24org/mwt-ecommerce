<?php

declare(strict_types=1);

return [
    'providers' => [
        'pathao' => [
            /*
             * Keep disabled until real merchant credentials
             * and a Pathao store ID are configured.
             */
            'enabled' => (bool) env(
                'PATHAO_ENABLED',
                false,
            ),

            /*
             * Production:
             * https://api-hermes.pathao.com
             *
             * Sandbox/test accounts may use a different
             * base URL supplied by Pathao.
             */
            'base_url' => env(
                'PATHAO_BASE_URL',
                'https://api-hermes.pathao.com',
            ),

            /*
             * Preferred when the merchant panel provides a
             * usable access token directly.
             */
            'access_token' => env(
                'PATHAO_ACCESS_TOKEN',
            ),

            /*
             * Fallback OAuth-style password grant used by
             * Pathao Courier Merchant API accounts that
             * require /issue-token.
             */
            'client_id' => env(
                'PATHAO_CLIENT_ID',
            ),

            'client_secret' => env(
                'PATHAO_CLIENT_SECRET',
            ),

            'username' => env(
                'PATHAO_USERNAME',
            ),

            'password' => env(
                'PATHAO_PASSWORD',
            ),

            /*
             * Pathao merchant pickup store.
             */
            'store_id' => (int) env(
                'PATHAO_STORE_ID',
                0,
            ),

            /*
             * Current Pathao Courier values:
             * 48 = Normal Delivery
             * 2  = Parcel
             */
            'delivery_type' => (int) env(
                'PATHAO_DELIVERY_TYPE',
                48,
            ),

            'item_type' => (int) env(
                'PATHAO_ITEM_TYPE',
                2,
            ),

            /*
             * Used only when the ShippingMethod metadata
             * does not provide:
             *
             * metadata.pathao.item_weight_kg
             */
            'default_item_weight_kg' => (float) env(
                'PATHAO_DEFAULT_ITEM_WEIGHT_KG',
                0.5,
            ),

            'connect_timeout_seconds' => (int) env(
                'PATHAO_CONNECT_TIMEOUT',
                5,
            ),

            'timeout_seconds' => (int) env(
                'PATHAO_TIMEOUT',
                15,
            ),

            /*
             * Pathao publicly documents Callback URL + Secret
             * configuration, but the public material reviewed
             * for Phase 8.6G does not define the exact request
             * signature/header/HMAC verification contract.
             *
             * Keep inbound webhook ingestion fail-closed. No
             * public webhook route is registered until that
             * provider contract is verified from the merchant
             * Developer API documentation.
             */
            'webhook' => [
                'accept_inbound' => false,

                'secret' => env(
                    'PATHAO_WEBHOOK_SECRET',
                ),

                'reserved_path' => '/webhooks/shipping/pathao',
            ],
        ],

        'steadfast' => [
            /*
             * Steadfast merchant API credentials are stored
             * encrypted in shipping_provider_accounts.
             *
             * The API host remains application-controlled.
             */
            'base_url' => env(
                'STEADFAST_BASE_URL',
                'https://portal.packzy.com/api/v1',
            ),

            'connect_timeout_seconds' => (int) env(
                'STEADFAST_CONNECT_TIMEOUT',
                5,
            ),

            'timeout_seconds' => (int) env(
                'STEADFAST_TIMEOUT',
                15,
            ),
        ],
    ],
];
