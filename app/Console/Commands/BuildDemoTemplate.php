<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('demo:build-template {--output= : Where to write the template (defaults to config demo.sandbox.template)}')]
#[Description('Migrate and seed a fresh demo template that visitor sandboxes are copied from')]
class BuildDemoTemplate extends Command
{
    public function handle(): int
    {
        $output = (string) ($this->option('output') ?: config('demo.sandbox.template'));
        $building = $output.'.building-'.getmypid();

        if (! is_dir(dirname($output))) {
            mkdir(dirname($output), 0775, true);
        }

        @unlink($building);
        touch($building);

        $previous = DB::getDefaultConnection();
        config(['database.connections.template.database' => $building]);
        DB::purge('template');
        DB::setDefaultConnection('template');

        try {
            $this->call('migrate', ['--database' => 'template', '--force' => true]);
            $this->call('db:seed', ['--class' => DemoSeeder::class, '--database' => 'template', '--force' => true]);

            DB::statement('VACUUM');
        } catch (\Throwable $e) {
            @unlink($building);

            throw $e;
        } finally {
            DB::purge('template');
            DB::setDefaultConnection($previous);
        }

        // Atomic: a sandbox being copied right now gets the old file or the
        // new one, never half of either.
        rename($building, $output);

        $this->components->info('Demo template written to '.$output.' ('.round(filesize($output) / 1024).' KB).');

        return self::SUCCESS;
    }
}
