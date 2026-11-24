<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * The audit trail every workflow step writes to, shown as a timeline.
 */
trait HasActivityLog
{
    /** @return MorphMany<ActivityLog, $this> */
    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable')
            ->latest('created_at')
            ->latest('id');
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, string $description, ?User $by = null, array $properties = []): ActivityLog
    {
        return $this->activities()->create([
            'user_id' => $by?->id,
            'action' => $action,
            'description' => $description,
            'properties' => $properties ?: null,
            'created_at' => now(),
        ]);
    }
}
