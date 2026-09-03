<?php

/**
 * PayPal Setting & API Credentials
 * Created by Raza Mehdi <srmk@outlook.com>.
 */

return [
    'mode' => env('PAYPAL_MODE', 'sandbox'), // Can only be 'sandbox' Or 'live'. If empty or invalid, 'live' will be used.
    'sandbox' => [
        'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
        'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET', ''),
        'app_id' => 'APP-80W284485P519543T', // Sandbox app_id is always this fixed value.
    ],
    'live' => [
        'client_id' => env('PAYPAL_LIVE_CLIENT_ID', ''),
        'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET', ''),
        // Live app_id: log in to developer.paypal.com → My Apps & Credentials →
        // select your app → the App ID shown at the top (starts with "APP-").
        'app_id' => env('PAYPAL_LIVE_APP_ID', ''),
    ],

    // ID del webhook registrado en el dashboard de PayPal (Apps & Credentials
    // → tu app → Add Webhook, apuntando a la ruta de notify_url de abajo) —
    // requiere una URL pública HTTPS, no se puede registrar contra localhost.
    // PayPalGateway::handleWebhook() (REQ-3.3) lo usa para verificar la firma
    // localmente (verifyWebHookLocally()), sin llamar de vuelta a la API.
    'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),

    // Id del Producto de PayPal ("ZertixPOS SaaS") que agrupa los Billing Plans
    // de los 3 planes comerciales (Emprendedor/PyME/Pro). Se crea una sola vez
    // — `paypal:sync-plans` (REQ-3.13) lo crea solo si esto está vacío, e
    // imprime el id para guardarlo acá.
    'product_id' => env('PAYPAL_PRODUCT_ID', ''),

    // Comisión real de PayPal para transacciones (nacionales e internacionales)
    // en República Dominicana — confirmada contra paypal.com/do/webapps/mpp/merchant-fees
    // (no la cuenta sandbox: una cuenta sandbox no tiene tarifas reales que
    // consultar, ver docs/features/v1.3.0.md §3.3). Único lugar que se toca si
    // la tasa cambia — usado por Plan::grossPrice() (REQ-4.5/REQ-3.13) para que
    // el precio que se le manda a PayPal, y el que se le muestra al cliente en
    // el Wizard, sean siempre el mismo cálculo.
    'fee_percentage' => (float) env('PAYPAL_FEE_PERCENTAGE', 0.054),
    'fee_fixed_usd' => (float) env('PAYPAL_FEE_FIXED_USD', 0.30),

    'payment_action' => env('PAYPAL_PAYMENT_ACTION', 'Sale'), // Can only be 'Sale', 'Authorization' or 'Order'
    'currency' => env('PAYPAL_CURRENCY', 'USD'),
    'notify_url' => env('PAYPAL_NOTIFY_URL', ''), // Change this accordingly for your application.
    'locale' => env('PAYPAL_LOCALE', 'en_US'), // force gateway language  i.e. it_IT, es_ES, en_US ... (for express checkout only)
    'validate_ssl' => env('PAYPAL_VALIDATE_SSL', true), // Validate SSL when creating api client.
    'timeout' => env('PAYPAL_TIMEOUT', 30), // Total request timeout in seconds.
    'connect_timeout' => env('PAYPAL_CONNECT_TIMEOUT', 10), // Connection timeout in seconds.
    'max_retries' => env('PAYPAL_MAX_RETRIES', 2), // Retries on 5xx / connection errors (0 to disable). Uses exponential backoff.
];
