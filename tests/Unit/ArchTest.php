<?php

use App\Models\Scopes\OrganizationScope;

arch('only the tenancy layer and platform admin code may reference the organization scope')
    ->expect('App')
    ->not->toUse(OrganizationScope::class)
    ->ignoring([
        'App\Models\Concerns\BelongsToOrganization',
        'App\Platform',
        // Parents aren't members of the organizations their kids play for, so
        // their own guardian records are looked up across organizations here.
        'App\Support\GuardianAccounts',
    ]);
