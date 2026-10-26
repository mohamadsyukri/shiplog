<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    seedAccess();
    $this->admin = superAdmin();
});

it('groups permissions under their sidebar labels', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.roles.create'))
        ->assertOk()
        ->assertSee('Imports')
        ->assertSee('List')
        ->assertSee('Edit');
});

it('creates a role with the ticked permissions', function (): void {
    $this->actingAs($this->admin)->post(route('admin.roles.store'), [
        'name' => 'Uploader',
        'permissions' => ['imports.index', 'imports.create'],
    ])->assertRedirect(route('admin.roles.index'));

    $role = Role::findByName('Uploader', 'web');
    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe(['imports.create', 'imports.index']);
});

it('rejects a permission that does not exist', function (): void {
    $this->actingAs($this->admin)->post(route('admin.roles.store'), [
        'name' => 'Odd Role',
        'permissions' => ['reports.nuke'],
    ])->assertSessionHasErrors('permissions.0');
});

it('keeps the super-admin role name fixed', function (): void {
    $superAdmin = Role::findByName(config('access.super_admin_role'), 'web');

    $this->actingAs($this->admin)->put(route('admin.roles.update', $superAdmin), ['name' => 'Boss'])
        ->assertSessionHasErrors('name');
});

it('refuses to delete a role that still has users, or the super-admin role', function (): void {
    $role = Role::findByName('User', 'web');
    User::factory()->create()->assignRole($role);
    $superAdmin = Role::findByName(config('access.super_admin_role'), 'web');

    $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $role))->assertSessionHas('error');
    $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $superAdmin))->assertSessionHas('error');

    expect(Role::query()->whereIn('id', [$role->id, $superAdmin->id])->count())->toBe(2);
});

it('deletes an unused role', function (): void {
    $role = Role::findOrCreate('Sementara', 'web');

    $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $role))->assertRedirect(route('admin.roles.index'));

    expect(Role::query()->where('name', 'Sementara')->exists())->toBeFalse();
});
