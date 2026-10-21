<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\CostCenter;
use App\Models\FarmNote;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Party;
use App\Models\Reminder;
use App\Support\Tr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The search box in the top bar: animals by number or name, people, stock items, ledger
 * entries by number or words, diary notes, reminders and categories — each only for those
 * allowed to see it. Pages themselves are matched in the browser.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->string('q'));

        if (mb_strlen($q) < 1) {
            return response()->json(['results' => []]);
        }

        $user = $request->user();
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
        $has = fn (string $ability) => $user->hasAbility($ability);
        $results = [];

        if ($has('view-reports')) {
            foreach (Animal::query()->with('costCenter:id,name')
                ->where(fn (Builder $w) => $w->where('tag', 'like', $like)->orWhere('name', 'like', $like)->orWhere('breed', 'like', $like))
                ->orderByRaw("status = 'active' desc")->orderBy('tag')->limit(6)->get() as $animal) {
                $results[] = ['group' => Tr::get('গরু'), 'title' => trim($animal->tag.' '.($animal->name ?? '')), 'hint' => $animal->costCenter->name.($animal->status !== 'active' ? ' · '.Tr::get(Animal::STATUSES[$animal->status] ?? '') : ''), 'href' => "/animals/{$animal->id}", 'icon' => 'cow'];
            }

            foreach (CostCenter::query()->where('name', 'like', $like)->orWhere('code', 'like', $like)->orderBy('sort')->limit(5)->get() as $center) {
                $results[] = ['group' => Tr::get('ক্যাটাগরি ও প্রজেক্ট'), 'title' => $center->name, 'hint' => $center->is_active ? $center->code : Tr::get('বন্ধ'), 'href' => "/centers/{$center->id}", 'icon' => 'folder'];
            }

            foreach (Party::query()->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orderBy('name')->limit(5)->get() as $party) {
                $results[] = ['group' => Tr::get('মানুষ ও প্রতিষ্ঠান'), 'title' => $party->name, 'hint' => $party->phone ?? '', 'href' => '/entries?party='.$party->id, 'icon' => 'user'];
            }

            foreach (Item::query()->where('name', 'like', $like)->orderBy('name')->limit(5)->get() as $item) {
                $results[] = ['group' => Tr::get('স্টকের মাল'), 'title' => $item->name, 'hint' => Tr::get('আছে :qty', ['qty' => rtrim(rtrim(number_format($item->qty_on_hand, 2, '.', ''), '0'), '.').' '.Tr::get(Item::UNITS[$item->unit] ?? $item->unit)]), 'href' => '/stock', 'icon' => 'package'];
            }

            foreach (JournalEntry::query()->where(fn (Builder $w) => $w->where('number', 'like', $like)->orWhere('description', 'like', $like)->orWhere('reference', 'like', $like))
                ->orderByDesc('date')->orderByDesc('id')->limit(6)->get() as $entry) {
                $results[] = ['group' => Tr::get('খাতার এন্ট্রি'), 'title' => $entry->description, 'hint' => $entry->number.' · '.$entry->date->format('d/m/Y'), 'href' => "/entries/{$entry->id}", 'icon' => 'receipt'];
            }

            foreach (FarmNote::query()->where(fn (Builder $w) => $w->where('title', 'like', $like)->orWhere('body', 'like', $like))
                ->orderByDesc('date')->limit(5)->get() as $note) {
                $results[] = ['group' => Tr::get('ডায়েরি'), 'title' => $note->title, 'hint' => Tr::get(FarmNote::KINDS[$note->kind] ?? '').' · '.$note->date->format('d/m/Y'), 'href' => '/diary', 'icon' => 'note'];
            }
        }

        foreach (Reminder::query()->visibleTo($user)->where('title', 'like', $like)->orderBy('due_at')->limit(5)->get() as $reminder) {
            $results[] = ['group' => Tr::get('রিমাইন্ডার'), 'title' => $reminder->title, 'hint' => $reminder->due_at->format('d/m g:i A'), 'href' => '/reminders', 'icon' => 'alarm'];
        }

        return response()->json(['results' => $results]);
    }
}
