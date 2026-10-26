<?php

use App\Models\User;

beforeEach(fn () => seedAccess());

it('sends guests to the sign-in page', function (): void {
    $this->get(route('board.index'))->assertRedirect(route('login'));
});

it('shows the sign-in page in English', function (): void {
    $this->get(route('login'))->assertOk()->assertSee('Sign in')->assertSee('Forgot password?');
});

it('signs in an active user and opens the board', function (): void {
    $user = User::factory()->create(['email' => 'aminah@example.com']);

    $this->post(route('login'), ['email' => 'aminah@example.com', 'password' => 'password'])
        ->assertRedirect(route('board.index'));

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password with the generic message', function (): void {
    User::factory()->create(['email' => 'aminah@example.com']);

    $this->post(route('login'), ['email' => 'aminah@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

    $this->assertGuest();
});

it('keeps an inactive user out, after checking the password', function (): void {
    User::factory()->inactive()->create(['email' => 'aminah@example.com']);

    $this->post(route('login'), ['email' => 'aminah@example.com', 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Your account is inactive. Please contact the administrator.']);

    $this->assertGuest();
});

it('signs out', function (): void {
    $this->actingAs(User::factory()->create())->post(route('logout'))->assertRedirect('/');

    $this->assertGuest();
});

it('does not offer self-registration', function (): void {
    $this->get('/register')->assertNotFound();
});
