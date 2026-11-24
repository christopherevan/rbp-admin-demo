<?php

use Symfony\Component\Finder\Finder;

/*
 * The demo is public. Nothing from the real client may ship in it.
 */
it('contains no trace of the real client', function () {
    $files = Finder::create()
        ->files()
        ->in(array_map(base_path(...), ['app', 'config', 'database', 'resources', 'routes', 'docker']))
        ->exclude(['actions', 'routes', 'wayfinder'])
        ->append([base_path('README.md'), base_path('Dockerfile')]);

    $hits = [];

    foreach ($files as $file) {
        if (preg_match('/roda|beltindo|perkasa|rbpadmin|rbp admin|movex|modutech/i', $file->getContents(), $match)) {
            $hits[] = $file->getRelativePathname().': '.$match[0];
        }
    }

    expect($hits)->toBe([]);
});
