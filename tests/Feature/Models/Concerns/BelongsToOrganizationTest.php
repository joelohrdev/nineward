<?php

use App\Models\Organization;
use App\Support\CurrentOrganization;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\RecordTenantRecordNames;
use Tests\Fixtures\TenantRecord;

beforeEach(function () {
    Schema::create('tenant_records', function (Blueprint $table) {
        $table->id();
        $table->foreignId('organization_id')->constrained();
        $table->string('name');
    });
});

test('queries only return records belonging to the current organization', function () {
    $current = Organization::factory()->create();
    $other = Organization::factory()->create();
    TenantRecord::create(['organization_id' => $current->id, 'name' => 'Ours']);
    TenantRecord::create(['organization_id' => $other->id, 'name' => 'Theirs']);

    app(CurrentOrganization::class)->set($current);

    expect(TenantRecord::pluck('name')->all())->toBe(['Ours']);
});

test('queries return nothing when no organization is current', function () {
    $organization = Organization::factory()->create();
    TenantRecord::create(['organization_id' => $organization->id, 'name' => 'Hidden']);

    expect(TenantRecord::count())->toBe(0);
});

test('a record from another organization cannot be found by its id', function () {
    $current = Organization::factory()->create();
    $theirs = TenantRecord::create([
        'organization_id' => Organization::factory()->create()->id,
        'name' => 'Theirs',
    ]);

    app(CurrentOrganization::class)->set($current);

    expect(TenantRecord::find($theirs->id))->toBeNull();
});

test('new records are assigned to the current organization', function () {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);

    $record = TenantRecord::create(['name' => 'Filled in']);

    expect($record->organization_id)->toBe($organization->id);
});

test('a record cannot be created without an organization', function () {
    TenantRecord::create(['name' => 'Orphan']);
})->throws(QueryException::class);

test('queued jobs run under the organization that dispatched them', function () {
    $current = Organization::factory()->create();
    $other = Organization::factory()->create();
    TenantRecord::create(['organization_id' => $current->id, 'name' => 'Ours']);
    TenantRecord::create(['organization_id' => $other->id, 'name' => 'Theirs']);
    config(['queue.default' => 'database']);

    app(CurrentOrganization::class)->set($current);
    RecordTenantRecordNames::dispatch();

    app(CurrentOrganization::class)->clear();
    Context::flush();
    $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true]);

    expect(Cache::get('visible-tenant-records'))->toBe(['Ours']);
});
