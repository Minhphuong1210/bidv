<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class CheckAuthTele
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {


        if (!Auth::guard('web')->check()) {
            return redirect('/login');
        }
        $user = Auth::user();
        if (!$user->is_approved && $user->role !== 'admin' && !$request->is('/') && !$request->is('logout')) {
            return redirect('/')->with('error', 'Tài khoản của bạn đang chờ admin duyệt.');
        }

        return $next($request);

    }
}
