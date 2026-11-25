<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\SettingsRepository;

final class SettingsAdminController
{
    public function index(Request $request): Response
    {
        $settings = SettingsRepository::all();

        return Response::html(View::render('admin/settings', [
            'title'    => 'Store Settings — GlassCart Admin',
            'settings' => $settings,
            'errors'   => [],
        ], layout: 'admin'));
    }

    public function update(Request $request): Response
    {
        $flatMajor = (float)$request->input('flat_shipping', 5);
        $thresholdMajor = (float)$request->input('free_shipping_threshold', 75);

        $payload = [
            'store_name'                    => $request->string('store_name', 'GlassCart'),
            'currency_code'                 => $request->string('currency_code', 'USD'),
            'currency_symbol'               => $request->string('currency_symbol', '$'),
            'flat_shipping_cents'           => (int)round($flatMajor * 100),
            'free_shipping_threshold_cents' => (int)round($thresholdMajor * 100),
            'contact_email'                 => $request->string('contact_email'),
            'announcement'                  => $request->string('announcement'),

            // Payment Methods Activation Toggles
            'payment_cod_enabled'           => $request->bool('payment_cod_enabled'),
            'payment_bank_enabled'          => $request->bool('payment_bank_enabled'),
            'payment_easypaisa_enabled'     => $request->bool('payment_easypaisa_enabled'),
            'payment_jazzcash_enabled'      => $request->bool('payment_jazzcash_enabled'),
            'payment_card_enabled'          => $request->bool('payment_card_enabled'),

            // Automated JazzCash Gateway Credentials
            'jazzcash_mode'                 => $request->string('jazzcash_mode', 'sandbox'),
            'jazzcash_merchant_id'          => $request->string('jazzcash_merchant_id'),
            'jazzcash_password'             => $request->string('jazzcash_password'),
            'jazzcash_integrity_salt'       => $request->string('jazzcash_integrity_salt'),

            // Automated Easypaisa Gateway Credentials
            'easypaisa_mode'                => $request->string('easypaisa_mode', 'sandbox'),
            'easypaisa_store_id'            => $request->string('easypaisa_store_id'),
            'easypaisa_hash_key'            => $request->string('easypaisa_hash_key'),

            // Automated Bank / Raast Gateway Credentials (PayFast / SBP Aggregator)
            'payfast_mode'                  => $request->string('payfast_mode', 'sandbox'),
            'payfast_merchant_id'           => $request->string('payfast_merchant_id'),
            'payfast_secured_key'           => $request->string('payfast_secured_key'),

            // Visa / Mastercard (Stripe) Credentials
            'stripe_mode'                   => $request->string('stripe_mode', 'test'),
            'stripe_publishable_key'        => $request->string('stripe_publishable_key'),
            'stripe_secret_key'             => $request->string('stripe_secret_key'),
            'stripe_webhook_secret'         => $request->string('stripe_webhook_secret'),
        ];

        $validator = Validator::make($payload, [
            'store_name'    => 'required|min:2|max:50',
            'currency_code' => 'required|min:2|max:5',
            'contact_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return Response::html(View::render('admin/settings', [
                'title'    => 'Store Settings — GlassCart Admin',
                'settings' => array_merge(SettingsRepository::all(), $payload),
                'errors'   => $validator->errors(),
            ], layout: 'admin'), 422);
        }

        SettingsRepository::saveMany($payload);
        Session::instance()->flash('success', 'Store settings updated successfully.');
        return Response::redirect('/admin/settings');
    }
}
