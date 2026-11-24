<?php

/*
 * Every sandbox at its size cap must still fit the tmpfs they share, or a
 * handful of visitors filling theirs breaks the demo for everyone.
 */
it('fits the worst case of sandboxes on the sandbox volume', function () {
    preg_match("/o: '[^']*size=(\d+)m/", (string) file_get_contents(base_path('docker-compose.yml')), $match);

    expect($match)->toHaveCount(2);

    $volume = (int) $match[1] * 1024 * 1024;
    $worstCase = config('demo.sandbox.max_sandboxes') * config('demo.sandbox.max_bytes');

    expect($worstCase)->toBeLessThanOrEqual($volume);
});
