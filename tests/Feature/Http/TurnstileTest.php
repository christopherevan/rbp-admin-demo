<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\Turnstile;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    User::factory()->create([
        'username' => config('demo.personas.admin'),
        'role' => Role::Admin,
    ]);
});

it('allows entering without a token when Turnstile is not configured', function () {
    config()->set('services.turnstile.site_key', null);
    config()->set('services.turnstile.secret_key', null);

    $this->post(route('demo.enter'), ['role' => 'admin'])
        ->assertRedirect(route('dashboard'));
});

it('rejects entry without a token when Turnstile is configured', function () {
    config()->set('services.turnstile.site_key', 'test-site-key');
    config()->set('services.turnstile.secret_key', 'test-secret-key');

    Http::fake();

    $this->post(route('demo.enter'), ['role' => 'admin'])
        ->assertSessionHasErrors('cf-turnstile-response');

    $this->assertGuest();
    Http::assertNothingSent();
});

it('rejects entry when Cloudflare siteverify returns failure', function () {
    config()->set('services.turnstile.site_key', 'test-site-key');
    config()->set('services.turnstile.secret_key', 'test-secret-key');

    Http::fake([
        Turnstile::VERIFY_URL => Http::response(['success' => false]),
    ]);

    $this->post(route('demo.enter'), [
        'role' => 'admin',
        'cf-turnstile-response' => 'invalid-token',
    ])->assertSessionHasErrors('cf-turnstile-response');

    $this->assertGuest();
});

it('verifies the Turnstile token with Cloudflare and signs in on success', function () {
    config()->set('services.turnstile.site_key', 'test-site-key');
    config()->set('services.turnstile.secret_key', 'test-secret-key');

    Http::fake([
        Turnstile::VERIFY_URL => Http::response(['success' => true]),
    ]);

    $this->post(route('demo.enter'), [
        'role' => 'admin',
        'cf-turnstile-response' => 'valid-token',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    Http::assertSent(fn (Request $request): bool => $request->url() === Turnstile::VERIFY_URL
        && $request['secret'] === 'test-secret-key'
        && $request['response'] === 'valid-token');
});
