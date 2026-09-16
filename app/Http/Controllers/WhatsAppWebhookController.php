<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WhatsAppWebhookController extends Controller
{
    public function verifikasi(Request $request)
    {
        $token = config('otp.whatsapp.webhook_verify_token');
        $dikirim = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));
        abort_unless(is_string($token) && $token !== '' && is_string($dikirim)
            && hash_equals($token, $dikirim) && $mode === 'subscribe'
            && is_string($challenge) && preg_match('/^[0-9]{1,255}$/', $challenge), 403);

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function terima(Request $request)
    {
        $secret = config('otp.whatsapp.app_secret');
        $signature = $request->header('X-Hub-Signature-256');
        abort_unless(is_string($secret) && $secret !== '' && is_string($signature)
            && hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $secret), $signature), 403);

        $payload = $request->validate([
            'object' => ['required', 'in:whatsapp_business_account'],
            'entry' => ['required', 'array'],
            'entry.*.id' => ['required', 'string'],
            'entry.*.changes' => ['required', 'array'],
            'entry.*.changes.*.field' => ['required', 'string'],
            'entry.*.changes.*.value' => ['required', 'array'],
            'entry.*.changes.*.value.metadata.phone_number_id' => ['sometimes', 'string'],
            'entry.*.changes.*.value.statuses' => ['sometimes', 'array'],
            'entry.*.changes.*.value.statuses.*.id' => ['required', 'string', 'max:255'],
            'entry.*.changes.*.value.statuses.*.status' => ['required', 'string'],
            'entry.*.changes.*.value.statuses.*.timestamp' => ['required', 'regex:/^[0-9]{1,10}$/'],
            'entry.*.changes.*.value.statuses.*.errors' => ['sometimes', 'array'],
            'entry.*.changes.*.value.statuses.*.errors.*.code' => ['sometimes', 'integer'],
        ]);

        foreach ($payload['entry'] as $entry) {
            if ($entry['id'] !== (string) config('otp.whatsapp.waba_id')) {
                continue;
            }
            foreach ($entry['changes'] as $change) {
                if ($change['field'] !== 'messages') {
                    continue;
                }
                $value = $change['value'];
                if (data_get($value, 'metadata.phone_number_id') !== (string) config('otp.whatsapp.phone_number_id')) {
                    continue;
                }
                foreach ($value['statuses'] ?? [] as $status) {
                    $this->catatStatus($status);
                }
            }
        }

        return response()->json(['received' => true]);
    }

    private function catatStatus(array $event): void
    {
        $urutan = ['pending' => 0, 'accepted' => 1, 'sent' => 2, 'failed' => 2, 'delivered' => 3, 'read' => 4];
        if (! in_array($event['status'], ['sent', 'failed', 'delivered', 'read'], true)) {
            return;
        }

        DB::transaction(function () use ($event, $urutan): void {
            $log = WhatsAppMessage::where('message_id', $event['id'])->lockForUpdate()->first();
            if (! $log || $urutan[$event['status']] < ($urutan[$log->status] ?? 0)
                || ($log->status_at && (int) $event['timestamp'] < $log->status_at->timestamp)) {
                return;
            }
            $code = data_get($event, 'errors.0.code');
            $log->update([
                'status' => $event['status'],
                'status_at' => Carbon::createFromTimestamp((int) $event['timestamp']),
                'error' => $event['status'] === 'failed' ? (is_int($code) ? 'meta_'.$code : 'delivery_failed') : null,
            ]);
        });
    }
}
