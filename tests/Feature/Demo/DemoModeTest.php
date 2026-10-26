<?php

use App\Models\Entry;
use App\Models\Lane;
use Database\Seeders\DemoSeeder;
use Laravel\Fortify\Fortify;

/*
 * Routes are registered when the app boots, so setting the config is not enough: each test boots
 * the app again with DEMO_MODE on and migrates that app's own in-memory database. (Handing it the
 * one RefreshDatabase migrated fails: that connection is mid-transaction, and a seeder's own
 * transaction then meets "cannot start a transaction within a transaction".)
 * Afterwards the variable is removed and Fortify's static route switch turned back on, so demo
 * mode never leaks into other test files (a leaked switch drops the logout route the top bar needs).
 */
beforeEach(function (): void {
    putenv('DEMO_MODE=true');
    $_ENV['DEMO_MODE'] = $_SERVER['DEMO_MODE'] = 'true';
    $this->refreshApplication();
    $this->artisan('migrate');
});

afterEach(function (): void {
    putenv('DEMO_MODE');
    unset($_ENV['DEMO_MODE'], $_SERVER['DEMO_MODE']);
    Fortify::$registersRoutes = true;
});

it('shows the board to a visitor who is not signed in, under the demo banner', function (): void {
    Entry::factory()->for(Lane::factory()->create(['name' => 'Client portal']))->create(['summary' => 'Shipped the new invoice screen.']);

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Demo — made-up data', 'Client portal', 'Shipped the new invoice screen.'])
        ->assertSee('href="https://github.com/mohamadsyukri/shiplog"', false)
        ->assertDontSee('Sign out');
});

it('serves no page but the board, so nothing can be signed into or written', function (string $method, string $uri): void {
    $this->call($method, $uri)->assertNotFound();
})->with([
    'sign-in page' => ['GET', '/login'],
    'sign in' => ['POST', '/login'],
    'sign out' => ['POST', '/logout'],
    'password reset' => ['GET', '/forgot-password'],
    'profile' => ['GET', '/profile'],
    'import history' => ['GET', '/imports'],
    'upload form' => ['GET', '/imports/create'],
    'upload' => ['POST', '/imports'],
    'users' => ['GET', '/admin/users'],
    'announcements' => ['GET', '/announcements'],
]);

it('shows the seeded demo up to its last day, a page at a time', function (): void {
    $this->seed(DemoSeeder::class);

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Updated up to 28/09/2026', 'Client portal', 'Mobile app', 'Side project', 'Other', '28/09/2026'])
        ->assertSee('Older days');
});

it('tells the operator how to fill an empty demo', function (): void {
    $this->get('/')
        ->assertSee('php artisan db:seed --class=DemoSeeder')
        ->assertDontSee('php artisan shiplog:import');
});
