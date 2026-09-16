<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class DashboardNavigationService
{
    public function __construct(private readonly CapabilityService $capabilities)
    {
    }

    /**
     * Build the one dashboard tree for the current user.
     *
     * Navigation is presentation only. Destination authorization remains
     * server-side in middleware/controllers; this service only projects the
     * master registry into what the current principal may reasonably see.
     */
    public function forUser(?User $user): array
    {
        return collect(config('dashboard.navigation', []))
            ->map(fn (array $group) => $this->filterGroup($group, $user))
            ->filter()
            ->values()
            ->all();
    }

    private function filterGroup(array $group, ?User $user): ?array
    {
        if (! $this->authorized($group, $user)) {
            return null;
        }

        $items = collect($group['items'] ?? [])
            ->map(fn (array $item) => $this->filterItem($item, $user))
            ->filter()
            ->values()
            ->all();

        return $items === [] ? null : [
            'key' => $group['key'] ?? null,
            'label' => $group['label'],
            'icon' => $group['icon'],
            'items' => $items,
        ];
    }

    private function filterItem(array $item, ?User $user): ?array
    {
        if (! $this->authorized($item, $user)) {
            return null;
        }

        $routeName = $item['route'] ?? null;
        $planned = (bool) ($item['planned'] ?? false);

        if ($routeName !== null && ! Route::has($routeName)) {
            // A registry entry must never create a fake URL. If the module has
            // not shipped yet, retain an explicit, non-actionable placeholder.
            $planned = true;
            $routeName = null;
        }

        if ($routeName === null && ! $planned) {
            return null;
        }

        $href = null;
        $active = false;
        if ($routeName !== null) {
            $href = route($routeName).($item['fragment'] ?? '');
            $active = request()->routeIs($routeName);
            if ($active && isset($item['fragment'])) {
                $active = request()->getRequestUri() === $href;
            }
        }

        return [
            'key' => $item['key'] ?? null,
            'label' => $item['label'],
            'icon' => $item['icon'],
            'href' => $href,
            'active' => $active,
            'disabled' => $href === null,
            'planned' => $href === null,
            'permission' => $item['permission'] ?? null,
            'scope' => $item['scope'] ?? null,
        ];
    }

    private function authorized(array $definition, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (($definition['admin_only'] ?? false) && ! $user->isAdmin()) {
            return false;
        }

        $permission = $definition['permission'] ?? null;
        if ($permission !== null && ! $this->allows($user, $permission, $definition['scope'] ?? 'own')) {
            return false;
        }

        return true;
    }

    /**
     * Translate the existing capability store into the normalized permission
     * vocabulary used by navigation. Unknown granular permissions are denied
     * for non-admins until a real authorization source exists for them.
     */
    private function allows(User $user, string $permission, string $scope): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // The current schema has no team/organization/global scope relation.
        // Never imply broader access merely because a menu item exists.
        if (in_array($scope, ['team', 'organization', 'global'], true)) {
            return false;
        }

        $capability = match ($permission) {
            'documents.view' => null, // owning documents are inherent to dashboard access
            'editor.use' => 'can_type',
            'ai.use' => 'can_ai',
            'support.use' => 'can_support',
            default => null,
        };

        if ($capability === null) {
            return in_array($permission, ['documents.view'], true);
        }

        return $this->capabilities->allowed($user, $capability);
    }
}
