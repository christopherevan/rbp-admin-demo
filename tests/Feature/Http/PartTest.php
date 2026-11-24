<?php

use App\Models\Part;
use Inertia\Testing\AssertableInertia as Assert;

it('lists discontinued parts separately and restores them', function () {
    $admin = admin();
    $active = part('P-ACTIVE');
    $gone = part('P-GONE');

    $this->actingAs($admin)->delete(route('parts.destroy', $gone));
    expect($gone->fresh()->trashed())->toBeTrue();

    $this->get(route('parts.index'))->assertInertia(fn (Assert $page) => $page
        ->where('parts.total', 1)
        ->where('parts.data.0.part_number', $active->part_number)
        ->where('discontinuedCount', 1)
        ->where('filters.discontinued', false));

    $this->get(route('parts.index', ['discontinued' => 1]))->assertInertia(fn (Assert $page) => $page
        ->where('parts.total', 1)
        ->where('parts.data.0.part_number', $gone->part_number)
        ->where('parts.data.0.discontinued', true)
        ->where('filters.discontinued', true));

    $this->post(route('parts.restore', $gone))->assertRedirect();

    expect($gone->fresh()->trashed())->toBeFalse()
        ->and(Part::onlyTrashed()->count())->toBe(0);
});

it('lets only admins restore a discontinued part', function () {
    $part = part();
    $part->delete();

    $this->actingAs(salesRep())->post(route('parts.restore', $part));

    expect($part->fresh()->trashed())->toBeTrue();
});
