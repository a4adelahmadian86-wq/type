<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class DashboardNavigationService
{
    public function __construct(protected CapabilityService $capabilities) {}

    public function groups(?User $user = null): array
    {
        $user = $user ?: auth()->user();
        $definitions = config('dashboard.navigation', []);
        $groups = [];

        foreach ($definitions as $group) {
            if (! $this->authorized($group, $user)) {
                continue;
            }

            $items = [];
            foreach ($group['items'] ?? [] as $item) {
                if (! $this->authorized($item, $user)) {
                    continue;
                }
                $items[] = $this->normalizeItem($item);
            }

            if ($items === []) {
                continue;
            }

            $groups[] = [
                'key' => $group['key'] ?? null,
                'label' => $group['label'],
                'icon' => $group['icon'] ?? 'fa-circle',
                'items' => $items,
            ];
        }

        return $groups;
    }

    private function normalizeItem(array $item): array
    {
        $href = null;
        $active = false;
        $planned = false;

        $routeName = $item['route'] ?? null;
        $routeParams = $item['route_params'] ?? [];

        if ($routeName) {
            try {
                if (Route::has($routeName)) {
                    $href = route($routeName, $routeParams);
                    if (! empty($item['fragment'])) {
                        $href .= '#'.$item['fragment'];
                    }
                } else {
                    $href = null;
                    $planned = true;
                }
            } catch (\Throwable) {
                $href = null;
                $planned = true;
            }

            if ($href !== null) {
                $active = request()->routeIs($routeName);
                if ($active && ! empty($routeParams)) {
                    foreach ($routeParams as $key => $value) {
                        if ((string) request()->route($key) !== (string) $value) {
                            $active = false;
                            break;
                        }
                    }
                }
                if ($active && isset($item['fragment'])) {
                    $active = request()->url() === strtok($href, '#');
                }
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

    private function allows(User $user, string $permission, string $scope): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (in_array($scope, ['team', 'organization', 'global'], true)) {
            return false;
        }

        $capability = match ($permission) {
            'documents.view' => null,
            'editor.use' => 'can_type',
            'ai.use' => 'can_ai',
            'support.use' => 'can_support',
            default => null,
        };

        if ($capability === null) {
            return $permission === 'documents.view';
        }

        return $this->capabilities->allowed($user, $capability);
    }
}
