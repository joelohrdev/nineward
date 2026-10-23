<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Contracts\Session\Session;

/**
 * The organization a platform admin has entered to provide support, kept in
 * their session until they leave it.
 */
class SupportSession
{
    private const SESSION_KEY = 'support_session.organization_id';

    public function __construct(private Session $session) {}

    public function start(Organization $organization): void
    {
        $this->session->put(self::SESSION_KEY, $organization->id);
    }

    public function end(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function organizationId(): ?int
    {
        $id = $this->session->get(self::SESSION_KEY);

        return is_int($id) ? $id : null;
    }
}
