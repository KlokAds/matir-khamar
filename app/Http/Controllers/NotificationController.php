<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** The bell: what was sent to this person's phones, newest first, and what is still unseen. */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = AppNotification::query()->where('user_id', $request->user()->id)
            ->latest('id')->limit(40)->get(['id', 'title', 'body', 'url', 'read_at', 'created_at']);

        return response()->json([
            'items' => $items->map(fn (AppNotification $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'url' => $n->url,
                'read' => $n->read_at !== null,
                'at' => $n->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function read(Request $request): Response
    {
        AppNotification::query()->where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->noContent();
    }

    /**
     * For the phone's service worker when a push arrives without its words (or they cannot be
     * read): the newest unseen notifications of the last few minutes.
     */
    public function latest(Request $request): JsonResponse
    {
        return response()->json(AppNotification::query()->where('user_id', $request->user()->id)
            ->whereNull('read_at')->where('created_at', '>=', now()->subMinutes(15))
            ->latest('id')->limit(3)->get(['id', 'title', 'body', 'url', 'tag']));
    }
}
