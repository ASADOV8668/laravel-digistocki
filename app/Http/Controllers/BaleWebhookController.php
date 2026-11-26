<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\ListingStatusNotification;
use App\Services\BaleAdminMenu;
use App\Services\BaleAdminReport;
use App\Services\BaleBot;
use App\Services\ListingRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BaleWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret, BaleBot $bot, BaleAdminReport $report, BaleAdminMenu $menu)
    {
        abort_unless($bot->enabled() && hash_equals(config('services.bale.webhook_secret'), $secret), 404);

        $callbackId = $request->input('callback_query.id');
        $callback = is_string($callbackId) && $callbackId !== '' && strlen($callbackId) <= 128;
        $prefix = $callback ? 'callback_query.message' : 'message';
        $sender = $this->id($request->input($callback ? 'callback_query.from.id' : 'message.from.id'));
        $chat = $this->id($request->input($prefix.'.chat.id'));
        $update = $this->id($request->input('update_id'));
        $text = $request->input($callback ? 'callback_query.data' : 'message.text');
        $messageId = $callback ? $this->id($request->input($prefix.'.message_id')) : null;

        if ($request->input($prefix.'.chat.type') !== 'private' || ! $sender || ! $chat || $update === null || ! is_string($text) || ($callback && $messageId === null)) {
            return response()->noContent();
        }

        [$command, $listingId, $reason] = array_pad(preg_split('/\s+/u', trim($text), 3) ?: [], 3, null);
        $connection = DB::table('bale_admin_connections')->where('bale_user_id', $sender)->first();
        $admin = $connection?->user_id ? User::find($connection->user_id) : null;

        if (! $admin?->isAdmin() || ! $admin->is_active || $connection->chat_id !== $chat) {
            if ($callback) {
                $bot->call('answerCallbackQuery', ['callback_query_id' => $callbackId, 'text' => 'اتصال حساب مدیریت را بررسی کنید.']);

                return response()->noContent();
            }
            $reply = $command === '/start' && ! $connection?->user_id
                ? $this->pairingInstructions($sender, $chat)
                : 'برای استفاده از ربات، اتصال حساب ادمین در سایت را بررسی کنید.';

            return response()->noContent($bot->send($chat, $reply) ? 204 : 503);
        }

        if ($callback) {
            $bot->call('answerCallbackQuery', ['callback_query_id' => $callbackId]);
            [$reply, $buttons] = $this->button($admin, $sender, $chat, $update, $text, $menu, $report);
        } else {
            [$reply, $buttons] = match ($command) {
                '/report' => [$report->text(), $menu->reportButtons()],
                '/pending' => [$this->pending(), $menu->reportButtons()],
                '/approve', '/reject' => [$this->moderate($admin, $sender, $chat, $update, $command, $listingId, $reason), $menu->reportButtons()],
                default => $menu->home(),
            };
        }

        return response()->noContent($bot->send($chat, $reply, $buttons, $messageId) ? 204 : 503);
    }

    private function button(User $admin, string $sender, string $chat, string $update, string $data, BaleAdminMenu $menu, BaleAdminReport $report): array
    {
        [$action, $listingId, $reasonKey, $signature] = array_pad(explode(':', $data, 4), 4, null);
        if ($data === 'menu') {
            return $menu->home();
        }
        if ($data === 'report') {
            return [$report->text(), $menu->reportButtons()];
        }
        if ($data === 'queue' || ($action === 'queue' && $this->id($listingId))) {
            return $menu->queue($listingId);
        }
        if (! $this->id($listingId) || ! ($listing = Listing::find($listingId))) {
            return ['آگهی در دسترس نیست.', $menu->reportButtons()];
        }
        if ($action === 'view' || $listing->status !== ListingStatus::Pending) {
            return $menu->card($listing);
        }

        $reason = BaleAdminMenu::REJECTION_REASONS[$reasonKey] ?? null;
        $confirmation = "commit:{$listingId}:".($reasonKey ?? 'approve');
        $expected = substr(hash_hmac('sha256', "{$admin->id}:{$sender}:{$chat}:{$confirmation}", (string) config('services.bale.webhook_secret')), 0, 20);

        if ($action === 'commit' && ($reasonKey === 'approve' || $reason !== null) && $signature !== null && hash_equals($expected, $signature)) {
            $command = $reason === null ? '/approve' : '/reject';
            $result = $this->moderate($admin, $sender, $chat, $update, $command, $listingId, $reason);

            return [$result."\n".$listing->title, $menu->reportButtons()];
        }

        return match (true) {
            $action === 'approve' && $reasonKey === null, $action === 'reason' && $reason !== null
                => $menu->confirmation($listing, $reason, $confirmation.':'.$expected),
            $action === 'reject' => $menu->reasons($listing),
            $action === 'custom' => ["برای ثبت دلیل دلخواه، پیام زیر را با دلیل خود بفرستید.\n\n/reject {$listingId} دلیل رد آگهی", [[
                ['text' => 'بازگشت به دلایل', 'callback_data' => "reject:{$listingId}"],
            ]]],
            default => ['این دکمه معتبر نیست. بررسی را دوباره شروع کنید.', $menu->reportButtons()],
        };
    }

    private function id(mixed $value): ?string
    {
        return (is_int($value) || is_string($value)) && preg_match('/\A[0-9]{1,20}\z/', (string) $value)
            ? (string) $value
            : null;
    }

    private function pairingInstructions(string $sender, string $chat): string
    {
        $code = bin2hex(random_bytes(16));
        DB::table('bale_admin_connections')->insertOrIgnore(['bale_user_id' => $sender, 'chat_id' => $chat]);

        $issued = DB::table('bale_admin_connections')->where('bale_user_id', $sender)->whereNull('user_id')->update([
            'chat_id' => $chat,
            'code_hash' => hash('sha256', $code),
            'code_expires_at' => now()->addMinutes(10),
        ]);

        if ($issued !== 1) {
            return 'برای دیدن منو، دوباره /start را بفرستید.';
        }

        return "پس از ورود به پنل مدیریت، کد زیر را در صفحه اتصال ربات وارد کنید. کد تا ۱۰ دقیقه اعتبار دارد.\n\n"
            .$code."\n\n".route('admin.bale.show');
    }

    private function pending(): string
    {
        $listings = Listing::query()->where('status', ListingStatus::Pending->value)
            ->oldest('id')->limit(10)->get(['id', 'title']);

        if ($listings->isEmpty()) {
            return 'هیچ آگهی در انتظار تأیید وجود ندارد.';
        }

        return "آگهی‌های در انتظار تأیید (حداکثر ۱۰ آگهی)\n\n"
            .$listings->map(fn (Listing $listing) => "#{$listing->id} — ".mb_substr($listing->title, 0, 100))->implode("\n")
            ."\n\n".route('admin.listings.index', ['status' => 'pending']);
    }

    private function moderate(User $admin, string $sender, string $chat, string $update, string $command, ?string $listingId, ?string $reason): string
    {
        $rejecting = $command === '/reject';
        $reason = $rejecting ? trim($reason ?? '') : null;

        if (! $this->id($listingId) || ($rejecting && (mb_strlen($reason) < 5 || mb_strlen($reason) > 1000))) {
            return $rejecting ? 'قالب دستور: /reject 123 علت رد (۵ تا ۱۰۰۰ نویسه)' : 'قالب دستور: /approve 123';
        }

        return DB::transaction(function () use ($admin, $sender, $chat, $update, $listingId, $rejecting, $reason) {
            $actor = User::query()->lockForUpdate()->find($admin->id);
            $ability = $rejecting ? 'reject' : 'approve';
            if (! $actor?->isAdmin() || ! $actor->is_active || ! DB::table('bale_admin_connections')
                ->where('bale_user_id', $sender)->where('chat_id', $chat)->where('user_id', $actor->id)->exists()) {
                return 'دسترسی مدیریت ندارید.';
            }

            if (DB::table('bale_admin_actions')->where('update_id', $update)->exists()) {
                return 'این درخواست قبلاً انجام شده است.';
            }

            $listing = Listing::query()->lockForUpdate()->find($listingId);
            if (! $listing || $listing->status !== ListingStatus::Pending) {
                return 'آگهی در انتظار تأیید پیدا نشد.';
            }

            if (Gate::forUser($actor)->denies($ability, $listing)) {
                return 'دسترسی مدیریت این آگهی را ندارید.';
            }

            $rules = app(ListingRules::class);
            if ($rejecting ? ! $rules->canReject($listing) : ! $rules->canPublish($listing)) {
                return 'عملیات برای وضعیت فعلی آگهی مجاز نیست.';
            }

            $listing = $rejecting ? $rules->reject($listing, $reason) : $rules->publish($listing);
            $listing->user->notify(new ListingStatusNotification($listing, $listing->status->value, $reason));
            DB::table('bale_admin_actions')->insert([
                'update_id' => $update,
                'admin_id' => $actor->id,
                'listing_id' => $listing->id,
                'action' => $ability,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            return $rejecting ? 'آگهی رد شد.' : 'آگهی تأیید شد.';
        }, 3);
    }
}
