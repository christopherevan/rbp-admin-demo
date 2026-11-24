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
