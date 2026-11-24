<?php

use App\Services\DescriptionGuard;

$facts = ['Pitch (mm)' => ['25.4'], 'Width (mm)' => ['305', '610'], 'Load capacity (kg)' => ['2500']];
$manufacturer = 'Closed-surface modular belt for conveying cartons, trays and packaged products with hinge rods so a worn section can be replaced.';
$clean = 'Series 300 Flat Top Belt is a modular belt from Beltora with a 25.4 mm pitch, in widths of 305 and 610 mm. It gives small packs an even surface to ride on and a damaged row can be swapped on site instead of fitting a whole new belt, which keeps stoppages short and the line running. We can quote it together with matching sprockets and wear strips cut to your frame.';

it('passes a description that sticks to the facts', function () use ($facts, $manufacturer, $clean) {
    $result = (new DescriptionGuard)->check($clean, $facts, 'belt', 'Series 300 Flat Top Belt', $manufacturer);

    expect($result['errors'])->toBe([])->and($result['warnings'])->toBe([]);
});

it('flags figures that are not in the facts', function () use ($facts, $manufacturer, $clean) {
    $result = (new DescriptionGuard)->check($clean.' Rated for 6000 kg.', $facts, 'belt', 'Series 300 Flat Top Belt', $manufacturer);

    expect($result['errors'])->toHaveCount(1)->and($result['errors'][0])->toContain('6000');
});

it('only warns about single-digit numbers', function () use ($facts, $manufacturer, $clean) {
    $result = (new DescriptionGuard)->check($clean.' Fits 2 shifts a day.', $facts, 'belt', 'Series 300 Flat Top Belt', $manufacturer);

    expect($result['errors'])->toBe([])->and($result['warnings'])->toHaveCount(1);
});

it('flags compliance claims without evidence, hyphenated or not', function (string $claim) use ($facts, $manufacturer, $clean) {
    $result = (new DescriptionGuard)->check($clean.' '.$claim, $facts, 'belt', 'Series 300 Flat Top Belt', $manufacturer);

    expect($result['errors'])->toHaveCount(1)->and($result['errors'][0])->toContain('Compliance claim');
})->with(['The belt is FDA-compliant.', 'It is food grade.', 'Certified Halal.']);

it('flags a product described as the wrong kind of thing', function () use ($facts, $manufacturer, $clean) {
    $text = str_replace('is a modular belt', 'is a table top chain', $clean);
    $result = (new DescriptionGuard)->check($text, $facts, 'belt', 'Series 300 Flat Top Belt', $manufacturer);

    expect($result['errors'][0])->toContain('table top chain');
});

it('flags copy lifted from the manufacturer', function () use ($facts, $manufacturer) {
    $text = 'Series 300 is a modular belt. '.$manufacturer.' We stock widths of 305 and 610 mm and will happily quote it for your conveyor along with sprockets and wear strips, cut to length.';
    $result = (new DescriptionGuard)->check($text, $facts, 'belt', 'Series 300 Flat Top Belt', $manufacturer);

    expect($result['similarity'])->toBeGreaterThan(DescriptionGuard::SIMILARITY_LIMIT)
        ->and(implode(' ', $result['errors']))->toContain('manufacturer');
});
