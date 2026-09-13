<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivateStorageDisk
{
    public function handle(Request $request, Closure $next): Response
    {
        $private = (array) config('filesystems.disks.private', []);

        if (($private['driver'] ?? null) !== 'local' || empty($private['root'])) {
            $private = [
                'driver' => 'local',
                'root' => storage_path('app/private'),
                'throw' => false,
            ];
            config(['filesystems.disks.private' => $private]);
        }

        $root = (string) $private['root'];
        if (! is_dir($root)) {
            @mkdir($root, 0775, true);
        }

        return $next($request);
    }
}
