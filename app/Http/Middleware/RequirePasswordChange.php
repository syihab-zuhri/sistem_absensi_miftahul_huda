<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user?->must_change_password && ! $request->routeIs('profile.*', 'password.*', 'student.settings', 'student.password.update', 'logout')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Ganti sandi awal melalui pengaturan akun.'], 403);
            }

            return redirect()->route($user->hasRole('siswa') ? 'student.settings' : 'profile.edit')->with('warning', 'Ganti sandi awal sebelum menggunakan akun.');
        }

        return $next($request);
    }
}
