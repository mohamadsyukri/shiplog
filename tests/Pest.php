<?php

use App\Models\User;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

/*
 * Feature tests run on sqlite in memory (phpunit.xml), rebuilt for every test — never a real database.
 */
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/**
 * Roles, route permissions and menus: the reference data every screen relies on.
 */
function seedAccess(): void
{
    test()->seed([RoleSeeder::class, PermissionSeeder::class, MenuSeeder::class]);
}

function userWithPermissions(string ...$permissions): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

function superAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(config('access.super_admin_role'));

    return $user;
}

/**
 * The README's example Shiplog file as the JSON text an import reads, optionally broken on purpose.
 *
 * @param  array<string, mixed>  $changes  dot path => new value
 * @param  list<string>  $remove  dot paths to drop
 */
function shiplogFile(array $changes = [], array $remove = []): string
{
    $file = json_decode((string) file_get_contents(base_path('tests/Fixtures/shiplog-example.json')), true, 512, JSON_THROW_ON_ERROR);

    foreach ($changes as $path => $value) {
        data_set($file, $path, $value);
    }
    Arr::forget($file, $remove);

    return json_encode($file, JSON_THROW_ON_ERROR);
}
