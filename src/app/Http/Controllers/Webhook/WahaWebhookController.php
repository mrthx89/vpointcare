<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Waha\WahaWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WahaWebhookController extends Controller
{
    public function __invoke(Request $request, WahaWebhookProcessor $processor, ?string $token = null): JsonResponse
    {
        $expectedToken = config('services.waha.webhook_token');

        if ($expectedToken && ! hash_equals($expectedToken, (string) $token)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid webhook token.',
            ], 403);
        }

        $result = $processor->process($request->all());

        return response()->json($result);
    }
}
