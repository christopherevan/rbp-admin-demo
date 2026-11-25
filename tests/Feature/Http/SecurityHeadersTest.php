<?php

it('sends a script nonce in the CSP and on the inline theme script', function () {
    $response = $this->get('/')->assertOk();

    preg_match("/script-src 'self' 'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $match);

    expect($match)->toHaveCount(2);
    $response->assertSee('<script nonce="'.$match[1].'">', false);
});

it('ignores unknown appearance values in the inline theme script', function () {
    $this->withUnencryptedCookie('appearance', "dark';alert(1)//")
        ->get('/')
        ->assertOk()
        ->assertSee("const appearance = 'system'", false)
        ->assertDontSee('alert(1)', false);
});

it('allows Cloudflare Turnstile in CSP only when configured', function () {
    $without = (string) $this->get('/')->assertOk()->headers->get('Content-Security-Policy');
    expect($without)->not->toContain('challenges.cloudflare.com');

    config()->set('services.turnstile.site_key', 'test-site-key');
    config()->set('services.turnstile.secret_key', 'test-secret-key');

    $with = (string) $this->get('/')->assertOk()->headers->get('Content-Security-Policy');
    expect($with)
        ->toContain('script-src')
        ->toContain('https://challenges.cloudflare.com')
        ->toContain("frame-src 'self' https://challenges.cloudflare.com");
});
