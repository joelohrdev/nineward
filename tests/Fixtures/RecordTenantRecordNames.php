<?php

namespace Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Records which tenant records a queued job can see.
 */
class RecordTenantRecordNames implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::put('visible-tenant-records', TenantRecord::query()->orderBy('name')->pluck('name')->all());
    }
}
