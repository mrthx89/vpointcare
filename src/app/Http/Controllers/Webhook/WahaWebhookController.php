<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiAutoReplyService;
use App\Services\Waha\WahaWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WahaWebhookController extends Controller
{
    public function __invoke(Request $request, WahaWebhookProcessor $processor, AiAutoReplyService $autoReply, ?string $token = null): JsonResponse
    {
        $expectedToken = config('services.waha.webhook_token');

        if ($expectedToken && ! hash_equals($expectedToken, (string) $token)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid webhook token.',
            ], 403);
        }

        $result = $processor->process($request->all());

        if (($result['ok'] ?? false) && ! empty($result['chat_id'])) {
            try {
                $result['auto_reply'] = $autoReply->handleIncomingChat((string) $result['chat_id']);
            } catch (Throwable $exception) {
                Log::error('AI auto reply failed after WAHA webhook.', [
                    'chat_id' => $result['chat_id'],
                    'message' => $exception->getMessage(),
                ]);

                $result['auto_reply'] = [
                    'ok' => false,
                    'message' => 'AI auto reply gagal, webhook tetap diterima.',
                ];
            }
        }

        return response()->json($result);
    }
}
