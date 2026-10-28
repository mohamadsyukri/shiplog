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

it('gives the figures each of the last 60 days up to the newest day covered, with the sources of each lane\'s card', function (): void {
    $portal = Lane::factory()->create(['key' => 'portal', 'name' => 'Client portal', 'color' => '#2563eb', 'sort' => 1]);
    $side = Lane::factory()->create(['key' => 'side', 'name' => 'Side project', 'color' => '#16a34a', 'sort' => 2]);
    $source = ['type' => 'commit', 'ref' => '3f9c2ab', 'label' => 'A commit', 'url' => null];
    Entry::factory()->for($portal)->create(['date' => '2026-10-21', 'sources' => [$source, $source]]);
    Entry::factory()->for($side)->create(['date' => '2026-10-21', 'sources' => [$source]]);
    // 22/10 back 59 days is 24/08: the first day counted; 23/08 is one too old.
    Entry::factory()->for($portal)->create(['date' => '2026-08-24', 'sources' => [$source]]);
    Entry::factory()->for($side)->create(['date' => '2026-08-23', 'sources' => [$source]]);
    Import::factory()->create(['range_from' => '2026-10-15', 'range_to' => '2026-10-22']);

    $figures = $this->actingAs($this->user)->get(route('board.index'))
        ->assertSee('window.boardFigures', false)
        ->assertSee('Days with work')
        ->viewData('page')->figures;

    expect($figures['until'])->toBe('2026-10-22')
        ->and($figures['lanes'])->toBe([
            ['key' => 'portal', 'name' => 'Client portal', 'color' => '#2563eb'],
            ['key' => 'side', 'name' => 'Side project', 'color' => '#16a34a'],
        ])
        ->and($figures['days'])->toHaveCount(60)
        ->and($figures['days'][0])->toBe(['date' => '2026-08-24', 'sources' => ['portal' => 1]])
        ->and($figures['days'][58])->toBe(['date' => '2026-10-21', 'sources' => ['portal' => 2, 'side' => 1]])
        ->and($figures['days'][59])->toBe(['date' => '2026-10-22', 'sources' => []]);
});

it('leads with the figures on the newest page only', function (): void {
    Entry::factory()->create(['date' => '2026-10-21']);
    Entry::factory()->create(['date' => '2026-10-19']);

    $this->actingAs($this->user)->get(route('board.index', ['before' => '2026-10-21']))
        ->assertSee('19/10/2026')
        ->assertDontSee('window.boardFigures', false)
        ->assertDontSee('Days with work');
});

it('finds cards by their summary or a source\'s label or ref, in any case, and leaves the rest out', function (): void {
    $portal = Lane::factory()->create(['key' => 'portal', 'name' => 'Client portal', 'sort' => 1]);
    $side = Lane::factory()->create(['key' => 'side', 'name' => 'Side project', 'sort' => 2]);
    $source = fn (string $ref, string $label): array => ['type' => 'commit', 'ref' => $ref, 'label' => $label, 'url' => null];
    Entry::factory()->for($portal)->create(['date' => '2026-10-21', 'summary' => 'Fixed the Delivery fee.', 'sources' => [$source('1a2b3c4', 'Fees')]]);
    Entry::factory()->for($side)->create(['date' => '2026-10-21', 'summary' => 'Sketched the weekly summary.', 'sources' => [$source('5d6e7f8', 'Sketch')]]);
    Entry::factory()->for($portal)->create(['date' => '2026-10-20', 'summary' => 'Paired on checkout.', 'sources' => [$source('3f9c2ab', 'Orders/Checkout: stop charging the DELIVERY fee twice')]]);
    Entry::factory()->for($side)->create(['date' => '2026-10-19', 'summary' => 'Wrote the install steps.', 'sources' => [$source('9a8b7c6', 'Docs')]]);

    $this->actingAs($this->user)->get(route('board.index', ['q' => 'delivery']))
        ->assertSee('Cards matching “delivery”.')
        ->assertSeeInOrder(['Fixed the Delivery fee.', 'Paired on checkout.'])
        ->assertDontSee('Sketched the weekly summary.')
        ->assertDontSee('19/10/2026')
        ->assertDontSee('window.boardFigures', false)
        ->assertSee('name="q" id="app-search" value="delivery"', false);

    // A ref, and a label with a slash, which the JSON column stores escaped.
    $this->actingAs($this->user)->get(route('board.index', ['q' => '3F9C2AB']))->assertSee('Paired on checkout.')->assertDontSee('Fixed the Delivery fee.');
    $this->actingAs($this->user)->get(route('board.index', ['q' => 'orders/checkout']))->assertSee('Paired on checkout.');
    // The JSON's own key names are not text on a card.
    $this->actingAs($this->user)->get(route('board.index', ['q' => 'label']))->assertSee('No cards match “label”.');
});

it('keeps the search on the links to older and newest days', function (): void {
    $lane = Lane::factory()->create();
    $dates = collect(range(0, 30))->map(fn (int $i): CarbonImmutable => CarbonImmutable::parse('2026-10-21')->subDays($i));
    $dates->each(fn (CarbonImmutable $date) => Entry::factory()->for($lane)->create(['date' => $date->toDateString(), 'summary' => 'Deployed the release.']));
    $oldestShown = $dates[29];

    $this->actingAs($this->user)->get(route('board.index', ['q' => 'deployed']))
        ->assertSee('href="'.e(route('board.index', ['before' => $oldestShown->toDateString(), 'q' => 'deployed'])).'"', false);

    $this->actingAs($this->user)->get(route('board.index', ['before' => $oldestShown->toDateString(), 'q' => 'deployed']))
        ->assertSee($dates[30]->format('d/m/Y'))
        ->assertSee('href="'.route('board.index', ['q' => 'deployed']).'"', false);
});

it('refuses a search longer than 100 characters', function (): void {
    $this->actingAs($this->user)->get(route('board.index', ['q' => str_repeat('a', 101)]))->assertSessionHasErrors('q');
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
