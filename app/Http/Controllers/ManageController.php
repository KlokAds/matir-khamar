<?php

namespace App\Http\Controllers;

use App\Services\ManageBook;
use App\Support\Tr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/** Add, rename or remove a dropdown's choices from inside the dropdown. */
class ManageController extends Controller
{
    public function store(Request $request, string $type, ManageBook $book): RedirectResponse
    {
        $this->authorizeType($type);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'extra' => ['nullable', 'array'],
        ]);

        $model = $book->create($type, $data['name'], $data['extra'] ?? []);
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('":name" যোগ হলো।', ['name' => $model->getAttribute('name')])]);

        return back();
    }

    public function update(Request $request, string $type, int $id, ManageBook $book): RedirectResponse
    {
        $this->authorizeType($type);
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);

        $before = (string) $book->find($type, $id)->getAttribute('name');
        $book->rename($type, $id, $data['name']);
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('":old" এখন থেকে ":new" নামে।', ['old' => $before, 'new' => trim($data['name'])])]);

        return back();
    }

    public function destroy(string $type, int $id, ManageBook $book): RedirectResponse
    {
        $this->authorizeType($type);
        $name = (string) $book->find($type, $id)->getAttribute('name');

        $result = $book->remove($type, $id);
        Inertia::flash('toast', ['type' => 'success', 'message' => $result === 'deleted'
            ? Tr::get('":name" মুছে ফেলা হলো।', ['name' => $name])
            : Tr::get('":name" হিসাবে ব্যবহার হয়েছে, তাই লুকিয়ে রাখা হলো — পুরোনো হিসাব ঠিক থাকবে।', ['name' => $name])]);

        return back();
    }

    private function authorizeType(string $type): void
    {
        abort_unless(array_key_exists($type, ManageBook::TYPES), 404);
        Gate::authorize(ManageBook::TYPES[$type]);
    }
}
