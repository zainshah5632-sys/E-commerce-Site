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
            'title'     => 'Store Settings — GlassCart Admin',
            'settings'  => $settings,
            'errors'    => [],
            'activeTab' => 'general',
        ], layout: 'admin'));
    }

    public function update(Request $request): Response
    {
        $activeTab = $request->string('active_tab', 'general');
        $validTabs = ['general', 'payments', 'shipping', 'notifications', 'security'];
        if (!in_array($activeTab, $validTabs, true)) {
            $activeTab = 'general';
        }

        $flatMajor = (float)$request->input('flat_shipping', 5);
        $thresholdMajor = (float)$request->input('free_shipping_threshold', 75);

        $payload = [
            // Tab 1: General Info
            'store_name'                    => $request->string('store_name', 'GlassCart'),
            'currency_code'                 => $request->string('currency_code', 'USD'),
            'currency_symbol'               => $request->string('currency_symbol', '$'),
            'contact_email'                 => $request->string('contact_email'),
            'announcement'                  => $request->string('announcement'),

            // Tab 2: Payments Activation Toggles
            'payment_cod_enabled'           => $request->bool('payment_cod_enabled'),
            'payment_bank_enabled'          => $request->bool('payment_bank_enabled'),
            'payment_easypaisa_enabled'     => $request->bool('payment_easypaisa_enabled'),
            'payment_jazzcash_enabled'      => $request->bool('payment_jazzcash_enabled'),
            'payment_card_enabled'          => $request->bool('payment_card_enabled'),

            // Tab 2: JazzCash Gateway Credentials
            'jazzcash_mode'                 => $request->string('jazzcash_mode', 'sandbox'),
            'jazzcash_merchant_id'          => $request->string('jazzcash_merchant_id'),
            'jazzcash_password'             => $request->string('jazzcash_password'),
            'jazzcash_integrity_salt'       => $request->string('jazzcash_integrity_salt'),

            // Tab 2: Easypaisa Gateway Credentials & Settlement Wallet
            'easypaisa_mode'                => $request->string('easypaisa_mode', 'sandbox'),
            'easypaisa_store_id'            => $request->string('easypaisa_store_id'),
            'easypaisa_hash_key'            => $request->string('easypaisa_hash_key'),
            'easypaisa_merchant_number'     => $request->string('easypaisa_merchant_number', '03120246309'),

            // Tab 2: Bank / Raast Gateway Credentials (PayFast) & Settlement Bank
            'payfast_mode'                  => $request->string('payfast_mode', 'sandbox'),
            'payfast_merchant_id'           => $request->string('payfast_merchant_id'),
            'payfast_secured_key'           => $request->string('payfast_secured_key'),
            'bank_name'                     => $request->string('bank_name', 'United Bank Limited (UBL)'),
            'bank_account_title'            => $request->string('bank_account_title', 'GlassCart Retail'),
            'bank_account_number'           => $request->string('bank_account_number', '4214 6182 4622 1918'),

            // Tab 2: Visa / Mastercard (Stripe) Credentials
            'stripe_mode'                   => $request->string('stripe_mode', 'test'),
            'stripe_publishable_key'        => $request->string('stripe_publishable_key'),
            'stripe_secret_key'             => $request->string('stripe_secret_key'),
            'stripe_webhook_secret'         => $request->string('stripe_webhook_secret'),

            // Tab 3: Shipping & Delivery
            'flat_shipping_cents'           => (int)round($flatMajor * 100),
            'free_shipping_threshold_cents' => (int)round($thresholdMajor * 100),

            // Tab 4: Notifications
            'notif_order_confirm'           => $request->bool('notif_order_confirm'),
            'notif_shipping_update'         => $request->bool('notif_shipping_update'),
            'notif_refund_advisory'         => $request->bool('notif_refund_advisory'),
            'notif_low_stock'               => $request->bool('notif_low_stock'),
            'notif_low_stock_threshold'     => max(1, (int)$request->input('notif_low_stock_threshold', 3)),
            'notif_daily_digest'            => $request->bool('notif_daily_digest'),
            'notif_admin_email'             => $request->string('notif_admin_email', $request->string('contact_email')),

            // Tab 5: API & Security
            'security_session_timeout'      => max(15, (int)$request->input('security_session_timeout', 120)),
            'security_max_login_attempts'   => max(1, (int)$request->input('security_max_login_attempts', 5)),
            'security_maintenance_mode'     => $request->bool('security_maintenance_mode'),
            'security_strict_ssl'           => $request->bool('security_strict_ssl'),
            'api_webhook_secret'            => $request->string('api_webhook_secret', 'whsec_98fbc184013490aa8efb019349cde012'),
        ];

        $rules = [
            'store_name'    => 'required|min:2|max:50',
            'currency_code' => 'required|min:2|max:5',
            'contact_email' => 'required|email',
        ];

        if (!empty($payload['notif_admin_email'])) {
            $rules['notif_admin_email'] = 'email';
        }

        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            return Response::html(View::render('admin/settings', [
                'title'     => 'Store Settings — GlassCart Admin',
                'settings'  => array_merge(SettingsRepository::all(), $payload),
                'errors'    => $validator->errors(),
                'activeTab' => $activeTab,
            ], layout: 'admin'), 422);
        }

        SettingsRepository::saveMany($payload);
        Session::instance()->flash('success', 'Store settings updated successfully.');
        return Response::redirect('/admin/settings#' . $activeTab);
    }
}
