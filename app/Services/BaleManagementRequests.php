<?php

namespace App\Services;

use App\Models\User;
use App\Support\PersianDate;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BaleManagementRequests
{
    public const TECHNICAL_ADMIN_OPTION = 'bale_management_technical_admin_id';

    private const STATUSES = [
        'open' => 'در انتظار پیگیری',
        'working' => 'در حال انجام',
        'done' => 'انجام‌شده',
    ];

    public function isExecutive(User $user): bool
    {
        return $user->isAdmin() && $user->is_active && $user->id === $this->executiveId();
    }

    public function technicalId(): int|false
    {
        $value = app(SystemOptions::class)->get(self::TECHNICAL_ADMIN_OPTION, config('services.bale.technical_admin_id'));

        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    }

    private function executiveId(): int|false
    {
        return filter_var(config('services.bale.executive_admin_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    }

    /** @return array<int, string> */
    public function members(): array
    {
        $executive = $this->executiveId();
        $technical = $this->technicalId();
        if ($executive === false || $technical === false || $executive === $technical) {
            return [];
        }

        $members = [$executive => 'مدیر ارشد', $technical => 'مدیر فنی'];

        return User::query()->whereKey(array_keys($members))->where('role', 'admin')->where('is_active', true)->count() === 2
            ? $members
            : [];
    }

    public function handles(string $command, bool $callback): bool
    {
        return $callback ? str_starts_with($command, 'work:') : in_array($command, ['/work', '/request', '/reply'], true);
    }

    public function respond(User $admin, string $update, string $text, bool $callback): array
    {
        $members = $this->members();
        if (! isset($members[$admin->id])) {
            return ['این بخش ویژهٔ مدیر ارشد و مدیر فنی است.', []];
        }

        $requests = DB::table('bale_management_requests')
            ->whereIn('sender_id', array_keys($members))
            ->whereIn('recipient_id', array_keys($members));

        if (! $callback) {
            [$command, $argument] = array_pad(preg_split('/\s+/u', trim($text), 2), 2, '');

            return match ($command) {
                '/request' => $this->create($requests, $admin->id, $members, $update, $argument),
                '/reply' => $this->reply($requests, $admin->id, $members, $update, $argument),
                default => $this->inbox($requests, $admin->id, $members),
            };
        }

        [, $action, $id, $argument] = array_pad(explode(':', $text, 4), 4, null);
        if ($action === 'inbox' && ($id === null || $this->isId($id))) {
            return $this->inbox($requests, $admin->id, $members, $id);
        }
        if ($action === 'new') {
            return $this->newInstructions($admin->id, $members);
        }

        $request = $this->isId($id) ? (clone $requests)->find($id) : null;
        if (! $request) {
            return ['درخواست در دسترس نیست.', [[['text' => 'صندوق مدیران', 'callback_data' => 'work:inbox']]]];
        }

        if ($action === 'status' && $this->isExecutive($admin) && in_array($argument, ['working', 'done'], true)) {
            (clone $requests)->where('id', $id)
                ->whereIn('status', $argument === 'working' ? ['open'] : ['open', 'working'])
                ->update(['status' => $argument, 'updated_at' => now()]);
            $request = (clone $requests)->find($id);
        }
        if ($action === 'reply') {
            return ["برای پاسخ به این درخواست پیام زیر را با متن خود بفرستید.\n\n/reply {$id} متن پاسخ", [
                [['text' => 'بازگشت به درخواست', 'callback_data' => "work:thread:{$id}"]],
            ]];
        }

        return $this->thread($request, $admin->id, $members, $action === 'thread' && $this->isId($argument) ? $argument : null);
    }

    private function inbox(Builder $requests, int $adminId, array $members, ?string $before = null): array
    {
        $rows = (clone $requests)->when($before !== null, fn (Builder $query) => $query->where('id', '<', $before))
            ->orderByDesc('id')->limit(9)->get();
        $buttons = [];
        foreach ($rows->take(8) as $request) {
            $buttons[] = [[
                'text' => '#'.$request->id.' '.mb_substr($request->subject, 0, 42).' · '.self::STATUSES[$request->status],
                'callback_data' => "work:thread:{$request->id}",
            ]];
        }
        if ($rows->count() > 8) {
            $buttons[] = [['text' => 'درخواست‌های قدیمی‌تر', 'callback_data' => 'work:inbox:'.$rows[7]->id]];
        }
        $buttons[] = [
            ['text' => 'درخواست جدید', 'callback_data' => 'work:new'],
            ['text' => 'منوی اصلی', 'callback_data' => 'menu'],
        ];

        return ["صندوق مشترک مدیر ارشد و مدیر فنی\nحساب شما: ".$members[$adminId].($rows->isEmpty() ? "\nدرخواستی برای نمایش وجود ندارد." : ''), $buttons];
    }

    private function newInstructions(int $adminId, array $members): array
    {
        $recipient = array_diff_key($members, [$adminId => true]);

        return ["گیرنده: ".reset($recipient)."\nموضوع را در خط اول و توضیح را در خط بعد بنویسید.\n\n/request موضوع درخواست\nتوضیح درخواست", [
            [['text' => 'بازگشت به صندوق', 'callback_data' => 'work:inbox']],
        ]];
    }

    private function create(Builder $requests, int $adminId, array $members, string $update, string $text): array
    {
        [$subject, $body] = array_map('trim', array_pad(preg_split('/\R/u', $text, 2), 2, ''));
        if ($subject === '' || mb_strlen($subject) > 120 || mb_strlen($body) > 600) {
            return $this->newInstructions($adminId, $members);
        }

        $recipient = array_key_first(array_diff_key($members, [$adminId => true]));
        DB::table('bale_management_requests')->insertOrIgnore([
            'sender_id' => $adminId, 'recipient_id' => $recipient, 'update_id' => $update,
            'subject' => $subject, 'body' => $body, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $request = (clone $requests)->where('update_id', $update)->where('sender_id', $adminId)->first();

        return $request ? $this->thread($request, $adminId, $members) : ['درخواست ثبت نشد.', []];
    }

    private function reply(Builder $requests, int $adminId, array $members, string $update, string $text): array
    {
        [$id, $body] = array_pad(preg_split('/\s+/u', $text, 2), 2, '');
        $request = $this->isId($id) ? (clone $requests)->find($id) : null;
        if (! $request || trim($body) === '' || mb_strlen($body) > 600) {
            return ['قالب پاسخ: /reply شماره درخواست متن پاسخ (حداکثر ۶۰۰ نویسه)', [
                [['text' => 'صندوق مدیران', 'callback_data' => 'work:inbox']],
            ]];
        }

        DB::table('bale_management_replies')->insertOrIgnore([
            'request_id' => $request->id, 'author_id' => $adminId, 'update_id' => $update,
            'body' => trim($body), 'created_at' => now(),
        ]);

        return $this->thread($request, $adminId, $members);
    }

    private function thread(object $request, int $adminId, array $members, ?string $before = null): array
    {
        $id = $request->id;
        $replies = DB::table('bale_management_replies')->where('request_id', $id)
            ->whereIn('author_id', array_keys($members))
            ->when($before !== null, fn (Builder $query) => $query->where('id', '<', $before))
            ->orderByDesc('id')->limit(5)->get();
        $text = "#{$id} · {$request->subject}\nفرستنده: ".$members[$request->sender_id]."\nگیرنده: ".$members[$request->recipient_id]
            ."\nوضعیت: ".self::STATUSES[$request->status]."\nزمان ثبت: ".PersianDate::format($request->created_at, 'Y/m/d H:i');
        if ($request->body !== '') {
            $text .= "\n\n".$request->body;
        }
        $visible = $replies->take(4);
        foreach ($visible->reverse() as $reply) {
            $text .= "\n\n".$members[$reply->author_id]." · ".PersianDate::format($reply->created_at, 'Y/m/d H:i')."\n".$reply->body;
        }
        $buttons = [];
        if ($adminId === $this->executiveId() && $request->status !== 'done') {
            $buttons[] = $request->status === 'open' ? [
                ['text' => 'در حال انجام', 'callback_data' => "work:status:{$id}:working"],
                ['text' => 'انجام شد', 'callback_data' => "work:status:{$id}:done"],
            ] : [['text' => 'انجام شد', 'callback_data' => "work:status:{$id}:done"]];
        }
        if ($replies->count() > 4) {
            $buttons[] = [['text' => 'پاسخ‌های قدیمی‌تر', 'callback_data' => "work:thread:{$id}:".$visible->last()->id]];
        }
        $buttons[] = [
            ['text' => 'پاسخ', 'callback_data' => "work:reply:{$id}"],
            ['text' => 'صندوق مدیران', 'callback_data' => 'work:inbox'],
        ];

        return [$text, $buttons];
    }

    private function isId(?string $value): bool
    {
        return $value !== null && preg_match('/\A[1-9][0-9]{0,18}\z/', $value) === 1;
    }
}
