<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BaleBot
{
    public function enabled(): bool
    {
        return filled(config('services.bale.token'))
            && preg_match('/\A[a-f0-9]{64}\z/', (string) config('services.bale.webhook_secret')) === 1;
    }

    public function send(string $chatId, string $text, array $buttons = [], ?string $messageId = null): bool
    {
        $payload = ['chat_id' => $chatId, 'text' => $text];
        if ($buttons !== []) {
            $payload['reply_markup'] = ['inline_keyboard' => $buttons];
        }

        if ($messageId !== null && $this->call('editMessageText', $payload + ['message_id' => $messageId])) {
            return true;
        }

        return $this->call('sendMessage', $payload);
    }

    public function call(string $method, array $payload): bool
    {
        $token = config('services.bale.token');

        try {
            $response = Http::connectTimeout(3)->timeout(8)
                ->withOptions(['allow_redirects' => false])
                ->post("https://tapi.bale.ai/bot{$token}/{$method}", $payload);

            return $response->successful() && $response->json('ok') === true;
        } catch (ConnectionException) {
            return false;
        }
    }
}
