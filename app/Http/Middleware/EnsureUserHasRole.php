<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if(! $user || ! in_array($user->role, $roles, true), 403);

        abort_if(
            $user->role === 'mentor'
                && in_array('mentor', $roles, true)
                && ! config('mkscholars.features.mentorship_enabled', false),
            404
        );

        if (! $user->canAccessProtectedArea()) {
            if ($request->expectsJson()) {
                abort(403, $user->approvalMessage());
            }

            $message = $user->approvalMessage();

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', $message);
        }

        return $next($request);
    }
}

