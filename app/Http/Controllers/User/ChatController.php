<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendChatMessageRequest;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Listing;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $conversations = ChatConversation::query()
            ->where(fn ($query) => $query->where('buyer_id', $userId)->orWhere('seller_id', $userId))
            ->with(['listing.primaryImage', 'listing.user.storefront', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('chats.index', compact('conversations'));
    }

    public function fromListing(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($request->user()->can('view', $listing), 403);
        abort_if($listing->isOwnedBy($request->user()), 422, 'برای آگهی خودتان امکان شروع چت وجود ندارد.');

        $conversation = ChatConversation::firstOrCreate([
            'listing_id' => $listing->id,
            'buyer_id' => $request->user()->id,
            'seller_id' => $listing->user_id,
        ]);

        return redirect()->route('chats.show', $conversation);
    }

    public function show(Request $request, ChatConversation $conversation)
    {
        $this->authorize('view', $conversation);
        $conversation->load(['listing.primaryImage', 'listing.user.storefront', 'buyer', 'seller']);
        $this->markIncomingMessagesAsRead($conversation, $request->user()->id);
        $messages = $conversation->messages()->with('user')->get();

        return view('chats.show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function messages(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $this->markIncomingMessagesAsRead($conversation, $request->user()->id);
        $messages = $conversation->messages()->with('user')->get();

        return response()->json([
            'html' => view('chats.partials.messages', [
                'messages' => $messages,
                'currentUserId' => $request->user()->id,
            ])->render(),
            'last_message_id' => $messages->last()?->id,
            'unread_count' => ChatMessage::unreadCountForUser($request->user()->id),
        ]);
    }

    public function send(SendChatMessageRequest $request, ChatConversation $conversation, ImageService $imageService): JsonResponse|RedirectResponse
    {
        $this->authorize('send', $conversation);
        $path = null;

        if ($request->hasFile('image')) {
            $path = $imageService->store($request->file('image'), 'chat-messages/'.$conversation->id, false)['path'];
        }

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body' => trim((string) $request->input('body')) ?: null,
            'image_path' => $path,
        ]);
        $conversation->forceFill(['last_message_at' => now()])->save();

        if (! $request->expectsJson()) {
            return back()->with('status', 'پیام ارسال شد.');
        }

        $messages = $conversation->messages()->with('user')->get();

        return response()->json([
            'html' => view('chats.partials.messages', [
                'messages' => $messages,
                'currentUserId' => $request->user()->id,
            ])->render(),
            'last_message_id' => $message->id,
        ]);
    }

    private function markIncomingMessagesAsRead(ChatConversation $conversation, int $userId): void
    {
        $conversation->messages()
            ->whereNull('read_at')
            ->where('user_id', '!=', $userId)
            ->update(['read_at' => now()]);
    }
}
