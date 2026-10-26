<?php

use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

/*
 * Renders every screen once. Catches what unit-level tests miss: a deleted route name, a Blade
 * error, a missing component or translation key.
 */
beforeEach(function (): void {
    seedAccess();
    $this->actingAs(superAdmin());
});

it('renders every screen', function (string $routeName, Closure $parameters): void {
    $this->get(route($routeName, $parameters()))->assertOk();
})->with([
    'board' => ['board.index', fn (): array => []],
    'import history' => ['imports.index', fn (): array => []],
    'import upload' => ['imports.create', fn (): array => []],
    'profile' => ['profile.edit', fn (): array => []],
    'user list' => ['admin.users.index', fn (): array => []],
    'user add' => ['admin.users.create', fn (): array => []],
    'user edit' => ['admin.users.edit', fn (): array => [User::factory()->create()]],
    'role list' => ['admin.roles.index', fn (): array => []],
    'role add' => ['admin.roles.create', fn (): array => []],
    'role edit' => ['admin.roles.edit', fn (): array => [Role::findByName('User', 'web')]],
    'super-admin role edit' => ['admin.roles.edit', fn (): array => [Role::findByName(config('access.super_admin_role'), 'web')]],
    'audit list' => ['admin.audit-trail.index', fn (): array => []],
    'audit view' => ['admin.audit-trail.show', fn (): array => [Activity::query()->latest('id')->firstOrFail()]],
]);

it('feeds every listing table', function (string $routeName): void {

    $this->getJson(route($routeName, ['draw' => 1]))->assertOk()->assertJsonStructure(['data', 'recordsTotal']);
})->with(['admin.users.datatable', 'admin.roles.datatable', 'admin.audit-trail.datatable']);
