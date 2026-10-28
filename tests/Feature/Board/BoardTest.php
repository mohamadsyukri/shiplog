<?php

use App\Models\Entry;
use App\Models\Import;
use App\Models\Lane;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    seedAccess();
    $this->user = User::factory()->create();
});

it('sends guests to the sign-in page', function (): void {
    $this->get(route('board.index'))->assertRedirect(route('login'));
});

it('opens the board to a signed-in user with no permissions', function (): void {
    $this->actingAs($this->user)->get(route('board.index'))->assertOk()->assertSee('Board');
});

it('shows the newest day first, with one column per lane in lane order', function (): void {
    $sideProject = Lane::factory()->create(['name' => 'Side project', 'sort' => 2]);
    $portal = Lane::factory()->create(['name' => 'Client portal', 'sort' => 1]);
    Entry::factory()->for($portal)->create(['date' => '2026-10-19', 'summary' => 'Oldest card.']);
    Entry::factory()->for($sideProject)->create(['date' => '2026-10-21', 'summary' => 'Newest card.']);
    Entry::factory()->for($portal)->create(['date' => '2026-10-20', 'summary' => 'Middle card.']);

    $this->actingAs($this->user)->get(route('board.index'))
        ->assertSeeInOrder(['Client portal', 'Side project', '21/10/2026'])
        ->assertSeeInOrder(['21/10/2026', 'Newest card.', '20/10/2026', 'Middle card.', '19/10/2026', 'Oldest card.']);
});

it('tells screen readers when a lane has no card that day', function (): void {
    $portal = Lane::factory()->create(['name' => 'Client portal', 'sort' => 1]);
    Lane::factory()->create(['name' => 'Side project', 'sort' => 2]);
    Entry::factory()->for($portal)->create(['date' => '2026-10-20']);

    $this->actingAs($this->user)->get(route('board.index'))
        ->assertSee('Side project: no card')
        ->assertDontSee('Client portal: no card');
});

it('opens a card to show its sources', function (): void {
    Entry::factory()->create(['date' => '2026-10-21', 'sources' => [
        ['type' => 'commit', 'ref' => '3f9c2ab', 'label' => 'Orders (Checkout): stop charging the delivery fee twice', 'url' => null],
        ['type' => 'artifact', 'ref' => 'weekly-summary-sketch', 'label' => 'Weekly summary sketch', 'url' => 'https://example.com/weekly-summary-sketch'],
    ]]);

    $this->actingAs($this->user)->get(route('board.index'))
        ->assertSee('2 sources')
        ->assertSeeInOrder(['Orders (Checkout): stop charging the delivery fee twice', '3f9c2ab'])
        ->assertSee('href="https://example.com/weekly-summary-sketch"', false);
});

it('names the lane and counts the sources on each card, which opens its details in the pane', function (): void {
    $portal = Lane::factory()->create(['key' => 'portal', 'name' => 'Client portal']);
    $entry = Entry::factory()->for($portal)->create(['date' => '2026-10-21', 'sources' => [
        ['type' => 'commit', 'ref' => '3f9c2ab', 'label' => 'Orders (Checkout): stop charging the delivery fee twice', 'url' => null],
    ]]);

    $this->actingAs($this->user)->get(route('board.index'))
        ->assertSee('data-card="board-card-'.$entry->id.'" aria-controls="board-pane"', false)
        ->assertSee('id="board-card-'.$entry->id.'" data-title="Client portal" data-date="Wednesday 21/10/2026"', false)
        ->assertSeeInOrder(['Client portal', '1 source', 'Sources (1)', 'data-copy="3f9c2ab"'], false)
        ->assertSee('id="board-pane"', false);
});

it('lists every lane in lane order in the Lanes filter', function (): void {
    Lane::factory()->create(['key' => 'side', 'name' => 'Side project', 'sort' => 2]);
    $portal = Lane::factory()->create(['key' => 'portal', 'name' => 'Client portal', 'sort' => 1]);
    Entry::factory()->for($portal)->create(['date' => '2026-10-21']);

    $this->actingAs($this->user)->get(route('board.index'))
        ->assertSee('Select all')
        ->assertSeeInOrder(['value="portal" data-name="Client portal"', 'value="side" data-name="Side project"'], false);
});

it('shows 30 days with work per page and links to the older days', function (): void {
    $lane = Lane::factory()->create();
    // Every other day, so a page counts days with work, not calendar days.
    $dates = collect(range(0, 30))->map(fn (int $i): CarbonImmutable => CarbonImmutable::parse('2026-10-21')->subDays($i * 2));
    $dates->each(fn (CarbonImmutable $date) => Entry::factory()->for($lane)->create(['date' => $date->toDateString()]));
    [$newest, $oldestShown, $nextPage] = [$dates[0], $dates[29], $dates[30]];

    $this->actingAs($this->user)->get(route('board.index'))
        ->assertSee($newest->format('d/m/Y'))
        ->assertSee($oldestShown->format('d/m/Y'))
        ->assertDontSee($nextPage->format('d/m/Y'))
        ->assertSee('href="'.route('board.index', ['before' => $oldestShown->toDateString()]).'"', false)
        ->assertDontSee('Newest days');

    $this->actingAs($this->user)->get(route('board.index', ['before' => $oldestShown->toDateString()]))
        ->assertSee($nextPage->format('d/m/Y'))
        ->assertDontSee($oldestShown->format('d/m/Y'))
        ->assertDontSee('Older days')
        ->assertSee('Newest days');
});

it('says how far the board is up to date from the newest day any import covered', function (): void {
    Entry::factory()->create(['date' => '2026-10-21']);
    Import::factory()->create(['range_from' => '2026-10-15', 'range_to' => '2026-10-21']);
    // A month backfilled afterwards must not move the line back.
    Import::factory()->create(['range_from' => '2026-09-01', 'range_to' => '2026-09-30']);

    $this->actingAs($this->user)->get(route('board.index'))->assertSee('Updated up to 21/10/2026');
});

it('explains how to run the first update when the board is empty', function (): void {
    $this->actingAs($this->user)->get(route('board.index'))
        ->assertSee('No cards yet.')
        ->assertSee('php artisan shiplog:import')
        ->assertDontSee('Updated up to');
});

it('refuses a before date that is not YYYY-MM-DD', function (): void {
    $this->actingAs($this->user)->get(route('board.index', ['before' => '21/10/2026']))->assertSessionHasErrors('before');
});
