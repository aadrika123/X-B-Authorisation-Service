<?php

namespace App\Pipelines\Citizen;

use Closure;

class SearchByEmail
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('email')) {
            return $next($request);
        }
        return $next($request)
            ->where('email', 'ilike', '%' . request()->input('email') . '%');
    }
}
