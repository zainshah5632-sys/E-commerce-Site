<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Supabase\Client;

final class HealthController
{
    public function index(Request $request): Response
    {
        $deep = $request->bool('deep');

        if (!$deep) {
            return Response::json(['status' => 'ok', 'timestamp' => time()]);
        }

        // Deep health check touches Supabase (keeps it warm per D-010)
        try {
            $client = new Client();
            $res = $client->get('site_settings', ['limit' => '1', 'select' => 'key']);

            if ($res->ok) {
                return Response::json([
                    'status'    => 'ok',
                    'database'  => 'connected',
                    'timestamp' => time(),
                ]);
            }

            return Response::json([
                'status'    => 'degraded',
                'database'  => 'error',
                'detail'    => $res->error,
                'timestamp' => time(),
            ], 503);
        } catch (\Throwable $e) {
            return Response::json([
                'status'    => 'error',
                'message'   => $e->getMessage(),
                'timestamp' => time(),
            ], 500);
        }
    }
}
