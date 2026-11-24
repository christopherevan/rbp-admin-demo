<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('demo:prune-sandboxes')->everyFifteenMinutes();

// Seeded dates are relative to "now", so rebuild nightly to keep them current.
// Existing sandboxes keep their data; new visitors get the fresh template.
Schedule::command('demo:build-template')->dailyAt('03:00')->withoutOverlapping();
