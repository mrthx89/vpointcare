<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WahaWebhookController extends Controller
{
    public function __invoke(Request $request, ?string $token = null): JsonResponse
    {
        $expectedToken = config('services.waha.webhook_token');

        if ($expectedToken && ! hash_equals($expectedToken, (string) $token)) {
            return response()->json([
                'ok' => false,
                'message' => __('ui.controllers.webhook.invalid_token'),
            ], 403);
        }

        $hmacKey = config('services.waha.webhook_hmac_key');

        if ($hmacKey && ! $this->validHmacSignature($request, (string) $hmacKey)) {
            return response()->json([
                'ok' => false,
                'message' => __('ui.controllers.webhook.invalid_hmac'),
            ], 403);
        }

        ProcessWebhookJob::dispatch($request->all());

        return response()->json([
            'ok' => true,
            'queued' => true,
            'message' => __('ui.scalability.webhook_queued'),
        ]);
    }

    private function validHmacSignature(Request $request, string $hmacKey): bool
    {
        $signature = (string) $request->header('X-Webhook-Hmac', '');
        $algorithm = strtolower((string) $request->header('X-Webhook-Hmac-Algorithm', 'sha512'));

        if ($signature === '' || $algorithm !== 'sha512') {
            return false;
        }

        $expected = hash_hmac('sha512', $request->getContent(), $hmacKey);

        return hash_equals($expected, $signature);
    }
}
