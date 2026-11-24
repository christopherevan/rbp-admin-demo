<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Support\Str;

/**
 * The integer id stays the primary key (foreign keys, the audit log's
 * morphs); the ULID in the `ulid` column is the public identifier in URLs,
 * so records can't be enumerated by counting.
 */
trait HasPublicUlid
{
    use HasUlids;

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Timestamp the ULID from the record's own creation time (an explicitly
     * backdated created_at, else the app clock) rather than the system clock,
     * so records replayed by the demo seeder sort in the order they were
     * created instead of all carrying the moment the template was built.
     */
    public function newUniqueId(): string
    {
        return strtolower((string) Str::ulid($this->created_at ?? now()));
    }
}
