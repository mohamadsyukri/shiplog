<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(fn () => seedAccess());

it('creates one permission per screen action, folding supporting routes into their screen', function (): void {
    $names = Permission::query()->pluck('name');

    expect($names)
        ->toContain('admin.users.index', 'admin.users.create', 'admin.users.edit', 'imports.index', 'imports.create')
        // datatable/export/pdf → index, store → create, update → edit (config/access.php aliases)
        ->not->toContain('admin.users.datatable', 'admin.users.store', 'admin.users.update', 'imports.store')
        // open to every signed-in user, so never a permission
        ->not->toContain('board.index', 'profile.edit');
});

it('lists stale permissions and deletes them only with --prune', function (): void {
    Permission::findOrCreate('reports.index', 'web');

    $this->artisan('permissions:sync')->expectsOutputToContain('reports.index')->assertSuccessful();
    expect(Permission::query()->where('name', 'reports.index')->exists())->toBeTrue();

    $this->artisan('permissions:sync --prune')->assertSuccessful();
    expect(Permission::query()->where('name', 'reports.index')->exists())->toBeFalse();
});

it('refuses a screen the user has no permission for', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('imports.index'))
        ->assertForbidden();
});

it('lets one permission open the screen and its data feed', function (): void {
    $user = userWithPermissions('admin.users.index');

    $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
    $this->actingAs($user)->getJson(route('admin.users.datatable'))->assertOk();
    $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
});

it('lets the super-admin role open everything', function (): void {
    $this->actingAs(superAdmin())->get(route('admin.users.index'))->assertOk();
});

it('opens the board and profile to any signed-in user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('board.index'))->assertOk();
    $this->actingAs($user)->get(route('profile.edit'))->assertOk();
});

it('shows only the sidebar items the user may open', function (): void {
    $this->actingAs(userWithPermissions('imports.index'))
        ->get(route('board.index'))
        ->assertSee('Imports')
        ->assertDontSee('Administration')
        ->assertDontSee('Audit Trail');

    $this->actingAs(superAdmin())
        ->get(route('board.index'))
        ->assertSee('Administration')
        ->assertSee('Audit Trail');
});

// The labels are seeded text, not __() keys, so dropping lang/ms alone would have left them Malay.
it('labels the sidebar in English', function (): void {
    $this->actingAs(superAdmin())
        ->get(route('board.index'))
        ->assertSeeInOrder(['Board', 'Imports', 'Administration', 'Users', 'Roles', 'Audit Trail'])
        ->assertDontSee(['Papan Pemuka', 'Pengumuman', 'Pentadbiran', 'Pengguna', 'Peranan', 'Jejak Audit']);
});
