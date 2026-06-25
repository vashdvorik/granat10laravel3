<?php

namespace App\Http\Controllers;

use App\Services\ApplicationHealthService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function ready(ApplicationHealthService $health): JsonResponse
    {
        $result = $health->readiness();

        return response()->json(
            [
                'status' => $result['ok'] ? 'ok' : 'unavailable',
                'checks' => $result['checks'],
            ],
            $result['ok'] ? 200 : 503
        );
    }
}
