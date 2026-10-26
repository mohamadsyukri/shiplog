<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ImportSource;
use App\Models\Entry;
use App\Models\Import;
use App\Models\Lane;
use App\Services\Imports\ImportService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;

/**
 * Invented lanes and cards for the public demo (config/shiplog.php). DatabaseSeeder never calls it:
 * run `php artisan db:seed --class=DemoSeeder` on a demo copy. It refuses anywhere else, so it can
 * never mix invented cards into a real diary. Re-running it writes the same board again.
 */
final class DemoSeeder extends Seeder
{
    // Fixed days and a fixed seed, so the demo and every screenshot of it stay the same.
    private const string FIRST_DAY = '2026-07-01';

    private const string LAST_DAY = '2026-09-28';

    private const int SEED = 2026;

    // Written as an import so the board's "Updated up to" line has a source; matched by file name.
    private const string FILE_NAME = 'demo.json';

    /** @var list<array{key: string, name: string, color: string, sort: int, weight: int}> */
    private const array LANES = [
        ['key' => 'client-portal', 'name' => 'Client portal', 'color' => '#2563eb', 'sort' => 1, 'weight' => 4],
        ['key' => 'mobile-app', 'name' => 'Mobile app', 'color' => '#9333ea', 'sort' => 2, 'weight' => 3],
        ['key' => 'side-project', 'name' => 'Side project', 'color' => '#16a34a', 'sort' => 3, 'weight' => 2],
        ['key' => 'other', 'name' => 'Other', 'color' => '#6b7280', 'sort' => 4, 'weight' => 1],
    ];

    /**
     * Invented work, per lane: what the card says, and the commit behind it (null: no repository).
     *
     * @var array<string, list<array{0: string, 1: ?string}>>
     */
    private const array WORK = [
        'client-portal' => [
            ['Fixed the invoice total that counted the delivery fee twice on split orders.', 'Orders (Checkout): stop charging the delivery fee twice on split orders'],
            ['Added a status filter to the order list and kept it between pages.', 'Orders (List): filter by status and keep the filter between pages'],
            ['Moved the address form to the shared postcode rules.', 'Customers (Addresses): check postcodes with the shared rules'],
            ['Built the monthly sales export from the same query as the report.', 'Reports (Sales): export the month from the report query'],
            ['Traced a slow customer search to a missing index and added it.', 'Customers (Search): add the missing name index'],
            ['Made the password reset link expire after an hour.', 'Accounts (Password reset): expire the link after an hour'],
            ['Showed the delivery slot on the order page and in the confirmation email.', 'Orders (Detail): show the delivery slot'],
            ['Gave every permission on the role screen a plain label.', 'Admin (Roles): label every permission in plain words'],
        ],
        'mobile-app' => [
            ['Added pull-to-refresh on the orders screen.', 'Orders: refresh the list by pulling down'],
            ['Stopped the sign-in form jumping when the keyboard opens.', 'Sign in: keep the form in place when the keyboard opens'],
            ['Cached product images so the catalogue opens offline.', 'Catalogue: cache images for offline use'],
            ['Moved push notifications to the new provider.', 'Notifications: switch to the new push provider'],
            ['Built order tracking with the driver on a live map.', 'Tracking: show the driver on a live map'],
            ['Cut start-up time by loading the catalogue after the first screen.', 'Startup: load the catalogue after the first screen'],
            ['Added dark mode colours to the settings and profile screens.', 'Settings: dark mode colours'],
            ['Prepared the store screenshots for the next release.', 'Release: store screenshots'],
        ],
        'side-project' => [
            ['Sketched the weekly summary screen and wrote down the open questions.', 'Summary: first sketch of the weekly screen'],
            ['Wrote the import file checks and a test for each rule.', 'Import: check every card and test each rule'],
            ['Tried three chart libraries for the trends page and kept the lightest.', 'Trends: pick the chart library'],
            ['Set up the deploy script and a nightly backup.', 'Ops: deploy script and nightly backup'],
            ['Wrote the install steps and tried them on a clean machine.', 'Docs: install steps checked on a clean machine'],
            ['Added keyboard shortcuts to move between days.', 'Board: move between days with the keyboard'],
        ],
        'other' => [
            ["Reviewed a colleague's pull request on the payments module.", null],
            ['Wrote up the notes from the team retrospective.', null],
            ['Drafted the estimate for the reporting module.', null],
            ['Updated the onboarding checklist for new developers.', null],
        ],
    ];

    /** @var list<string> */
    private const array FOLLOW_UPS = [
        'Added a test for the edge case.',
        'Checked it on a phone and on a slow connection.',
        'Walked the client through it on a call.',
        'Left a note on what is still open.',
        'Paired on it for an hour in the afternoon.',
        'Measured it before and after; the change holds.',
    ];

    public function run(): void
    {
        if (! config('shiplog.demo')) {
            throw new RuntimeException('DemoSeeder runs only with DEMO_MODE=true: anywhere else it would mix invented cards into a real diary.');
        }

        $random = new Randomizer(new Mt19937(self::SEED));

        $cards = DB::transaction(function () use ($random): int {
            $laneIds = [];
            foreach (self::LANES as $lane) {
                $laneIds[$lane['key']] = Lane::query()->updateOrCreate(
                    ['key' => $lane['key']],
                    ['name' => $lane['name'], 'color' => $lane['color'], 'sort' => $lane['sort']],
                )->id;
            }

            $import = Import::query()->updateOrCreate(['file_name' => self::FILE_NAME], [
                'range_from' => self::FIRST_DAY,
                'range_to' => self::LAST_DAY,
                'format' => ImportService::FORMAT,
                'source' => ImportSource::Command,
                'lanes_upserted' => count($laneIds),
                'user_id' => null,
            ]);

            $cards = 0;
            for ($day = CarbonImmutable::parse(self::FIRST_DAY); $day->lte(self::LAST_DAY); $day = $day->addDay()) {
                foreach ($this->lanesWorkedOn($random, $day) as $laneKey) {
                    // Dates as Y-m-d strings, as ImportService writes them (the board matches on them).
                    Entry::query()->updateOrCreate(
                        ['date' => $day->toDateString(), 'lane_id' => $laneIds[$laneKey]],
                        [...$this->card($random, $laneKey), 'import_id' => $import->id],
                    );
                    $cards++;
                }
            }

            $import->update(['entries_created' => $cards, 'entries_replaced' => 0]);

            return $cards;
        });

        $this->command?->info("Demo board: {$cards} cards in ".count(self::LANES).' lanes, '.self::FIRST_DAY.' to '.self::LAST_DAY.'.');
    }

    /**
     * Weekdays usually have work in one to three lanes; a weekend now and then has one. The first
     * and last days always have work, so the board spans exactly the stated range.
     *
     * @return list<string> lane keys
     */
    private function lanesWorkedOn(Randomizer $random, CarbonImmutable $day): array
    {
        $edge = $day->isSameDay(self::FIRST_DAY) || $day->isSameDay(self::LAST_DAY);

        if ($day->isWeekend()) {
            return $random->getInt(1, 4) === 1 ? ['side-project'] : [];
        }
        if (! $edge && $random->getInt(1, 100) <= 8) {
            return [];
        }

        $wanted = [1, 1, 2, 2, 3][$random->getInt(0, 4)];
        $pool = [];
        foreach (self::LANES as $lane) {
            array_push($pool, ...array_fill(0, $lane['weight'], $lane['key']));
        }

        $picked = [];
        while (count($picked) < $wanted) {
            $picked[$pool[$random->getInt(0, count($pool) - 1)]] = true;
        }

        return array_keys($picked);
    }

    /**
     * @return array{summary: string, sources: list<array{type: string, ref: string, label: string, url: ?string}>}
     */
    private function card(Randomizer $random, string $laneKey): array
    {
        [$summary, $commit] = self::WORK[$laneKey][$random->getInt(0, count(self::WORK[$laneKey]) - 1)];
        if ($random->getInt(0, 1) === 1) {
            $summary .= ' '.self::FOLLOW_UPS[$random->getInt(0, count(self::FOLLOW_UPS) - 1)];
        }

        $sources = $commit === null
            ? [['type' => 'note', 'ref' => 'note-'.bin2hex($random->getBytes(3)), 'label' => $summary, 'url' => null]]
            : [['type' => 'commit', 'ref' => substr(bin2hex($random->getBytes(4)), 0, 7), 'label' => $commit, 'url' => null]];

        if ($random->getInt(1, 10) <= 6) {
            $sources[] = ['type' => 'session', 'ref' => $this->sessionId($random), 'label' => 'Claude Code session', 'url' => null];
        }
        if ($random->getInt(1, 10) === 1) {
            $slug = 'demo-'.bin2hex($random->getBytes(3));
            $sources[] = ['type' => 'artifact', 'ref' => $slug, 'label' => 'Working notes', 'url' => 'https://example.com/'.$slug];
        }

        return ['summary' => $summary, 'sources' => $sources];
    }

    private function sessionId(Randomizer $random): string
    {
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($random->getBytes(16)), 4));
    }
}
