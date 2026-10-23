<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Context;

/**
 * The organization the current request or queued job is acting within.
 *
 * The ID is kept in hidden Context, so jobs dispatched during a request
 * run under the same organization without passing it around.
 */
#[Scoped]
class CurrentOrganization
{
    private const CONTEXT_KEY = 'organization_id';

    private ?Organization $organization = null;

    private bool $isSupportSession = false;

    public function set(Organization $organization): void
    {
        $this->organization = $organization;
        $this->isSupportSession = false;

        Context::addHidden(self::CONTEXT_KEY, $organization->id);
    }

    /**
     * Act within an organization as a platform admin providing support.
     */
    public function setForSupport(Organization $organization): void
    {
        $this->set($organization);
        $this->isSupportSession = true;
    }

    public function clear(): void
    {
        $this->organization = null;
        $this->isSupportSession = false;

        Context::forgetHidden(self::CONTEXT_KEY);
    }

    public function id(): ?int
    {
        return $this->organization->id ?? Context::getHidden(self::CONTEXT_KEY);
    }

    public function get(): ?Organization
    {
        if ($this->organization === null && ($id = $this->id()) !== null) {
            $this->organization = Organization::find($id);
        }

        return $this->organization;
    }

    public function isSupportSession(): bool
    {
        return $this->isSupportSession;
    }
}
