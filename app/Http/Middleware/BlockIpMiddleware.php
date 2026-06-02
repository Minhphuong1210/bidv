<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\BlockedIp;
use Illuminate\Support\Facades\Cache;

class BlockIpMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $userId = auth()->id();
$cacheKey = 'blocked_ip_' . $ip . '_' . ($userId ?? 'guest');
        $isBlocked = Cache::remember($cacheKey, 120, function () use ($ip, $userId) {
            $query = BlockedIp::where('ip_address', $ip);
            if ($userId) {
                $query->orWhere('user_id', $userId);
            }
            return $query->exists();
        });

        if ($isBlocked) {
            if ($request->expectsJson() || $request->isXmlHttpRequest()) {
                return response()->json(['status' => 'error', 'message' => 'Bạn đã bị chặn vĩnh viễn do hành vi spam.'], 403);
            }
            return response()->view('errors.blocked', [], 403);
        }

        return $next($request);
    }
}
