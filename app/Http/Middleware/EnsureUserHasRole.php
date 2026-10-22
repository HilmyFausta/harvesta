<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Allow the request only when the user has one of the given roles.
     *
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,staff').
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role;

        abort_unless(
            $role instanceof UserRole && in_array($role->value, $roles, true),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
