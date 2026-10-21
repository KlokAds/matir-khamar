<?php

use App\Enums\Role;
use App\Models\Animal;
use App\Models\CostCenter;
use App\Models\FarmNote;
use App\Models\HealthEvent;
use App\Models\MilkRecord;
use App\Models\User;
use App\Services\HerdCare;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    $this->gavi = CostCenter::query()->where('code', 'GAVI')->firstOrFail();
    $this->cow = fn (string $tag) => Animal::query()->create(['tag' => $tag, 'sex' => 'female', 'cost_center_id' => $this->gavi->id, 'status' => 'active', 'acquisition' => 'opening', 'acquired_on' => '2026-01-01']);
});

it('records a vaccine for several cows and reminds when the next dose is near', function () {
    $a = ($this->cow)('G-1');
    $b = ($this->cow)('G-2');

    $this->post('/health', [
        'target' => 'animals', 'animal_ids' => [$a->id, $b->id], 'kind' => 'vaccine', 'name' => 'ক্ষুরা রোগ (FMD)',
        'given_on' => now()->subMonths(6)->toDateString(), 'next_due_on' => now()->addDays(3)->toDateString(),
    ])->assertRedirect();

    expect(HealthEvent::query()->count())->toBe(2);
    $due = collect(app(HerdCare::class)->reminders())->where('kind', 'health');
    expect($due)->toHaveCount(2);

    // A newer dose for one cow clears her reminder.
    $this->post('/health', ['target' => 'animals', 'animal_ids' => [$a->id], 'kind' => 'vaccine', 'name' => 'ক্ষুরা রোগ (FMD)', 'given_on' => now()->toDateString(), 'next_due_on' => now()->addMonths(6)->toDateString()])->assertRedirect();
    expect(collect(app(HerdCare::class)->reminders())->where('kind', 'health'))->toHaveCount(1);

    $this->get('/health')->assertOk();
    $this->get("/animals/{$a->id}")->assertOk();
});

it('warns when a cow gives clearly less milk than her recent days', function () {
    $cow = ($this->cow)('G-9');

    foreach (range(10, 4) as $daysAgo) {
        MilkRecord::query()->create(['date' => now()->subDays($daysAgo)->toDateString(), 'session' => 'morning', 'cost_center_id' => $this->gavi->id, 'animal_id' => $cow->id, 'liters' => 10, 'recorded_by' => auth()->id()]);
    }

    foreach (range(3, 1) as $daysAgo) {
        MilkRecord::query()->create(['date' => now()->subDays($daysAgo)->toDateString(), 'session' => 'morning', 'cost_center_id' => $this->gavi->id, 'animal_id' => $cow->id, 'liters' => 6, 'recorded_by' => auth()->id()]);
    }

    $drop = collect(app(HerdCare::class)->reminders())->firstWhere('kind', 'milk');

    expect($drop['title'])->toContain('G-9')->toContain('40%')
        ->and($drop['urgent'])->toBeTrue();
});

it('keeps photos and voice notes with a diary page and serves them only when signed in', function () {
    Storage::fake('local');

    $this->post('/diary/notes', [
        'date' => now()->toDateString(), 'kind' => 'health', 'title' => 'লালীর পায়ে ক্ষত',
        'files' => [UploadedFile::fake()->image('pa.jpg'), UploadedFile::fake()->create('voice.webm', 50, 'audio/webm')],
    ])->assertRedirect();

    $note = FarmNote::query()->with('files')->firstOrFail();
    expect($note->files)->toHaveCount(2);

    $this->get('/diary/files/'.$note->files[0]->id)->assertOk();
    auth()->logout();
    $this->get('/diary/files/'.$note->files[0]->id)->assertRedirect('/login');
});
