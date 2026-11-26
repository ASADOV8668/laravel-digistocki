<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\ListingStatusNotification;
use App\Services\BaleAdminReport;
use App\Services\BaleBot;
use App\Services\ListingRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BaleWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret, BaleBot $bot, BaleAdminReport $report)
    {
        abort_unless($bot->enabled() && hash_equals(config('services.bale.webhook_secret'), $secret), 404);

        $sender = $this->id($request->input('message.from.id'));
        $chat = $this->id($request->input('message.chat.id'));
        $update = $this->id($request->input('update_id'));
        $text = $request->input('message.text');

        if ($request->input('message.chat.type') !== 'private' || ! $sender || ! $chat || $update === null || ! is_string($text)) {
            return response()->noContent();
        }

        [$command, $listingId, $reason] = array_pad(preg_split('/\s+/u', trim($text), 3) ?: [], 3, null);
        $connection = DB::table('bale_admin_connections')->where('bale_user_id', $sender)->first();
        $admin = $connection?->user_id ? User::find($connection->user_id) : null;

        if (! $admin?->isAdmin() || ! $admin->is_active || $connection->chat_id !== $chat) {
            $reply = $command === '/start' && ! $connection?->user_id
                ? $this->pairingInstructions($sender, $chat)
                : 'برای استفاده از ربات، اتصال حساب ادمین در سایت را بررسی کنید.';
        } else {
            $reply = match ($command) {
                '/report' => $report->text(),
                '/pending' => $this->pending(),
                '/approve', '/reject' => $this->moderate($admin, $sender, $update, $command, $listingId, $reason),
                default => implode("\n", [
                    'مدیریت دیجی استوکی',
                    '/report — گزارش سایت',
                    '/pending — آگهی‌های در انتظار',
                    '/approve 123 — تأیید آگهی',
                    '/reject 123 علت رد — رد آگهی',
                    'پنل مدیریت: '.route('admin.dashboard'),
                ]),
            };
        }

        return response()->noContent($bot->send($chat, $reply) ? 204 : 503);
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

    private function moderate(User $admin, string $sender, string $update, string $command, ?string $listingId, ?string $reason): string
    {
        $rejecting = $command === '/reject';
        $reason = $rejecting ? trim($reason ?? '') : null;

        if (! $this->id($listingId) || ($rejecting && (mb_strlen($reason) < 5 || mb_strlen($reason) > 1000))) {
            return $rejecting ? 'قالب دستور: /reject 123 علت رد (۵ تا ۱۰۰۰ نویسه)' : 'قالب دستور: /approve 123';
        }

        return DB::transaction(function () use ($admin, $sender, $update, $listingId, $rejecting, $reason) {
            $actor = User::query()->lockForUpdate()->find($admin->id);
            $ability = $rejecting ? 'reject' : 'approve';
            if (! $actor?->isAdmin() || ! $actor->is_active || ! DB::table('bale_admin_connections')
                ->where('bale_user_id', $sender)->where('user_id', $actor->id)->exists()) {
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
