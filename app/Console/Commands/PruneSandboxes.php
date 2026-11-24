<?php

namespace App\Console\Commands;

use App\Support\Sandbox\SandboxManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:prune-sandboxes')]
#[Description('Delete visitor sandboxes that are idle or past their maximum age')]
class PruneSandboxes extends Command
{
    public function handle(SandboxManager $sandboxes): int
    {
        $pruned = $sandboxes->prune();

        $this->components->info("Pruned {$pruned} ".str('sandbox')->plural($pruned).'; '.count($sandboxes->all()).' remaining.');

        return self::SUCCESS;
    }
}
