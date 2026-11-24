<?php

namespace App\Support\Sandbox;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Per-visitor SQLite sandboxes, copied from a pre-seeded template.
 *
 * A sandbox id is the creation time plus 24 random hex characters, so the
 * file name records its own age and can't be guessed. Requests are pointed at
 * a sandbox by switching the default connection's file (activate()), which is
 * safe only because every request boots fresh: never run this app under
 * Octane or FrankenPHP worker mode, or one visitor's connection would leak
 * into the next request.
 */
class SandboxManager
{
    private ?string $active = null;

    public function enabled(): bool
    {
        return (bool) config('demo.sandbox.enabled');
    }

    public function isValidId(mixed $id): bool
    {
        return is_string($id) && preg_match('/^\d{10}[a-f0-9]{24}$/', $id) === 1;
    }

    public function exists(string $id): bool
    {
        return $this->isValidId($id) && is_file($this->path($id));
    }

    public function path(string $id): string
    {
        return rtrim((string) config('demo.sandbox.path'), '/')."/{$id}.sqlite";
    }

    public function active(): ?string
    {
        return $this->active;
    }

    /**
     * Copy the template into a new sandbox. The copy goes to a temp file and
     * is renamed into place, so a sandbox is never seen half-written.
     */
    public function create(): string
    {
        $template = (string) config('demo.sandbox.template');

        if (! is_file($template)) {
            throw new RuntimeException('The demo template is missing. Run `php artisan demo:build-template`.');
        }

        $this->ensureDirectory();
        $this->evictIfFull();

        $id = sprintf('%010d', time()).bin2hex(random_bytes(12));
        $path = $this->path($id);
        $tmp = dirname($path)."/.{$id}.tmp";

        if (! copy($template, $tmp) || ! rename($tmp, $path)) {
            @unlink($tmp);

            throw new RuntimeException('Could not create a sandbox.');
        }

        return $id;
    }

    public function activate(string $id): void
    {
        if (! $this->exists($id)) {
            throw new RuntimeException('Unknown sandbox.');
        }

        $connection = (string) config('demo.sandbox.connection', 'sandbox');
        $path = $this->path($id);

        config(["database.connections.{$connection}.database" => $path]);
        DB::purge($connection);
        DB::setDefaultConnection($connection);

        // Last-used time drives idle pruning; skip the write on busy pages.
        if (filemtime($path) < time() - 60) {
            @touch($path);
        }

        $this->active = $id;
    }

    /** Point the connection back at the empty placeholder. */
    public function deactivate(): void
    {
        $connection = (string) config('demo.sandbox.connection', 'sandbox');

        config(["database.connections.{$connection}.database" => ':memory:']);
        DB::purge($connection);
        DB::setDefaultConnection($connection);

        $this->active = null;
    }

    /**
     * Delete a sandbox and any journal files beside it. Deleting a file that
     * an in-flight request still has open is safe on Linux: that request
     * finishes against the old inode.
     */
    public function destroy(string $id): void
    {
        if (! $this->isValidId($id)) {
            return;
        }

        $path = $this->path($id);

        foreach (['', '-journal', '-wal', '-shm'] as $suffix) {
            @unlink($path.$suffix);
        }

        if ($this->active === $id) {
            $this->active = null;
        }
    }

    /** The httpOnly, encrypted cookie that ties a browser to its sandbox. */
    public function cookie(string $id): Cookie
    {
        return cookie(
            name: (string) config('demo.sandbox.cookie'),
            value: $id,
            minutes: (int) config('demo.sandbox.max_age_minutes'),
            secure: config('session.secure'),
            httpOnly: true,
            sameSite: 'lax',
        );
    }

    public function size(string $id): int
    {
        return $this->exists($id) ? (int) filesize($this->path($id)) : 0;
    }

    public function createdAt(string $id): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp((int) substr($id, 0, 10), config('app.timezone'));
    }

    /**
     * Delete sandboxes idle or older than the configured limits, plus any
     * temp files a crashed copy left behind.
     */
    public function prune(): int
    {
        $idle = time() - 60 * (int) config('demo.sandbox.idle_minutes');
        $old = time() - 60 * (int) config('demo.sandbox.max_age_minutes');
        $pruned = 0;

        foreach ($this->all() as $id => $lastUsed) {
            if ($lastUsed < $idle || (int) substr($id, 0, 10) < $old) {
                $this->destroy($id);
                $pruned++;
            }
        }

        foreach (glob(rtrim((string) config('demo.sandbox.path'), '/').'/.*.tmp') ?: [] as $tmp) {
            if (filemtime($tmp) < time() - 600) {
                @unlink($tmp);
            }
        }

        return $pruned;
    }

    /** @return array<string, int> id => last-used timestamp */
    public function all(): array
    {
        $sandboxes = [];

        foreach (glob(rtrim((string) config('demo.sandbox.path'), '/').'/*.sqlite') ?: [] as $file) {
            $id = basename($file, '.sqlite');

            if ($this->isValidId($id)) {
                $sandboxes[$id] = (int) filemtime($file);
            }
        }

        return $sandboxes;
    }

    /** At the cap, drop the least recently used sandbox to make room. */
    private function evictIfFull(): void
    {
        $sandboxes = $this->all();
        $max = (int) config('demo.sandbox.max_sandboxes');

        if (count($sandboxes) < $max) {
            return;
        }

        asort($sandboxes);

        foreach (array_slice(array_keys($sandboxes), 0, count($sandboxes) - $max + 1) as $id) {
            $this->destroy($id);
        }
    }

    private function ensureDirectory(): void
    {
        $directory = (string) config('demo.sandbox.path');

        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Could not create {$directory}.");
        }
    }
}
