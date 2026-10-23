<?php

namespace App\Actions\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CreateOrganization
{
    /**
     * Create an organization owned by the user, start its free trial and
     * switch the user to it.
     */
    public function handle(User $owner, string $name): Organization
    {
        return DB::transaction(function () use ($owner, $name): Organization {
            $organization = new Organization([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
            ]);
            $organization->owner()->associate($owner);
            $organization->trial_ends_at = now()->addDays(config()->integer('services.stripe.trial_days'));
            $organization->save();

            $organization->addMember($owner, OrganizationRole::Owner);
            $owner->switchOrganization($organization);

            return $organization;
        });
    }

    /**
     * Organization pages live at /{slug}, so a slug can't be the first
     * segment of any of the app's own routes, like "dashboard" or "login".
     *
     * @return list<string>
     */
    private function reservedSlugs(): array
    {
        $segments = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $first = explode('/', trim($route->uri(), '/'))[0];

            if ($first !== '' && ! str_starts_with($first, '{')) {
                $segments[$first] = true;
            }
        }

        return array_keys($segments);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $this->reservedSlugs(), true) || Organization::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
