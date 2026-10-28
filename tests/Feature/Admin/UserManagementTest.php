<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    seedAccess();
    $this->admin = superAdmin();
});

it('lists users with their initials, roles and status', function (): void {
    User::factory()->create(['name' => 'Siti Aminah', 'is_active' => false])->assignRole('User');

    $rows = $this->actingAs($this->admin)
        ->getJson(route('admin.users.datatable', ['draw' => 1]))
        ->assertOk()
        ->json('data');
    $row = collect($rows)->first(fn (array $row): bool => str_contains($row['name'], 'Siti Aminah'));

    expect($row['name'])->toContain('aria-hidden="true">SA</span>Siti Aminah')
        ->and($row['roles'])->toContain('<span class="app-pill">User</span>')
        ->and($row['is_active'])->toContain('app-status is-off')->toContain('Inactive');
});

it('escapes a name in the listing now that its cell is HTML', function (): void {
    User::factory()->create(['name' => '<b>Ann</b> Lee']);

    $this->actingAs($this->admin)
        ->getJson(route('admin.users.datatable', ['draw' => 1]))
        ->assertOk()
        ->assertSee('&lt;b&gt;Ann&lt;\\/b&gt; Lee', false)
        ->assertDontSee('<b>Ann', false);
});

it('creates a user with roles', function (): void {
    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Siti Aminah',
        'email' => 'Siti@Example.com',
        'password' => 'rahsia-baharu-123',
        'password_confirmation' => 'rahsia-baharu-123',
        'roles' => ['User'],
        'is_active' => '1',
    ])->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'siti@example.com')->sole();
    expect($user->hasRole('User'))->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('rahsia-baharu-123', $user->password))->toBeTrue();
});

it('rejects a duplicate email and a missing role', function (): void {
    User::factory()->create(['email' => 'siti@example.com']);

    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Siti',
        'email' => 'siti@example.com',
        'password' => 'rahsia-baharu-123',
        'password_confirmation' => 'rahsia-baharu-123',
        'roles' => [],
    ])->assertSessionHasErrors(['email', 'roles']);
});

it('keeps the current password when the field is left blank', function (): void {
    $user = User::factory()->create();
    $oldHash = $user->password;

    $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
        'name' => 'Nama Baharu',
        'email' => $user->email,
        'password' => '',
        'password_confirmation' => '',
        'roles' => ['User'],
        'is_active' => '0',
    ])->assertRedirect(route('admin.users.index'));

    $user->refresh();
    expect($user->name)->toBe('Nama Baharu')
        ->and($user->password)->toBe($oldHash)
        ->and($user->is_active)->toBeFalse();
});

it('stops an administrator from locking themselves out', function (): void {
    $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
        'name' => $this->admin->name,
        'email' => $this->admin->email,
        'roles' => ['User'],
        'is_active' => '0',
    ])->assertSessionHasErrors(['is_active', 'roles']);

    expect($this->admin->fresh()->hasRole(config('access.super_admin_role')))->toBeTrue();
});
