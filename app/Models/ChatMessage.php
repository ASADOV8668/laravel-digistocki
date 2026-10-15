<?php

namespace App\Models;

use App\Services\ImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'chat_conversation_id',
        'user_id',
        'body',
        'image_path',
        'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::deleting(function (ChatMessage $message): void {
            if ($message->image_path) {
                app(ImageService::class)->delete($message->image_path);
            }
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnreadFor(Builder $query, int $userId): Builder
    {
        return $query->whereNull('read_at')->where('user_id', '!=', $userId)
            ->whereHas('conversation', fn (Builder $conversation) => $conversation
                ->where(fn (Builder $participants) => $participants
                    ->where('buyer_id', $userId)
                    ->orWhere('seller_id', $userId)));
    }

    public static function unreadCountForUser(int $userId): int
    {
        return static::query()->unreadFor($userId)->count();
    }
}
