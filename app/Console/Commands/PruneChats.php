<?php

namespace App\Console\Commands;

use App\Models\ChatConversation;
use App\Services\SystemOptions;
use Illuminate\Console\Command;

class PruneChats extends Command
{
    protected $signature = 'chats:prune {--days= : Override archive age in days}';

    protected $description = 'گفت‌وگوها و تصاویر قدیمی چت را طبق تنظیم آرشیو حذف می‌کند';

    public function handle(SystemOptions $options): int
    {
        $days = max(1, (int) ($this->option('days') ?: $options->chatArchiveDays()));
        $cutoff = now()->subDays($days);
        $deleted = 0;

        ChatConversation::query()
            ->where('created_at', '<', $cutoff)
            ->with('messages')
            ->chunkById(100, function ($conversations) use (&$deleted): void {
                foreach ($conversations as $conversation) {
                    $conversation->delete();
                    $deleted++;
                }
            });

        $this->info("{$deleted} گفت‌وگو حذف شد.");

        return self::SUCCESS;
    }
}
