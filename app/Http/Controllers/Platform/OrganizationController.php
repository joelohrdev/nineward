<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PlatformAuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $organizations = Organization::query()
            ->with(['owner:id,name,email', 'subscriptions'])
            ->withCount('members')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn ($owner) => $owner->where('email', 'like', "%{$search}%"));
            }))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Organization $organization): array => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'ownerEmail' => $organization->owner->email,
                'membersCount' => $organization->members_count,
                'status' => $this->status($organization),
                'createdAt' => $organization->created_at?->toIso8601String(),
            ]);

        return Inertia::render('platform/organizations/index', [
            'organizationPage' => $organizations,
            'search' => $search,
        ]);
    }

    public function show(Organization $organization): Response
    {
        $organization->load([
            'owner:id,name,email',
            'subscriptions',
            'members' => fn ($query) => $query->orderBy('users.name')->orderBy('users.id'),
        ]);

        $auditLogs = PlatformAuditLog::query()
            ->whereBelongsTo($organization)
            ->with('user:id,name')
            ->latest('created_at')
            ->latest('id')
            ->limit(20)
            ->get();

        return Inertia::render('platform/organizations/show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'ownerName' => $organization->owner->name,
                'ownerEmail' => $organization->owner->email,
                'status' => $this->status($organization),
                'trialEndsAt' => $organization->trial_ends_at?->toIso8601String(),
                'createdAt' => $organization->created_at?->toIso8601String(),
                'members' => $organization->members->map(fn ($member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->membership->role->label(),
                ])->all(),
            ],
            'auditLogs' => $auditLogs->map(fn (PlatformAuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'reason' => $log->reason,
                'userName' => $log->user->name,
                'createdAt' => $log->created_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    private function status(Organization $organization): string
    {
        return match (true) {
            $organization->subscribed() => 'subscribed',
            $organization->onGenericTrial() => 'trial',
            default => 'lapsed',
        };
    }
}
