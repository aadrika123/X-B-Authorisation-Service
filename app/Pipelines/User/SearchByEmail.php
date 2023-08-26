<?php

namespace App\Pipelines\User;

use Closure;

class SearchByEmail
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('email')) {
            return $next($request);
        }
        return $next($request)
            ->where('users.email', 'ilike', '%' . request()->input('email') . '%');
    }
}
