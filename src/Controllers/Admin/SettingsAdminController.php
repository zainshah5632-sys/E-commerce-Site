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
            'bank_instructions'             => $request->string('bank_instructions'),
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
