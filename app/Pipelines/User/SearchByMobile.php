<?php

namespace App\Pipelines\User;

use Closure;

class SearchByMobile
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('mobile')) {
            return $next($request);
        }
        return $next($request)
            ->where('users.mobile', 'ilike', '%' . request()->input('mobile') . '%');
    }
}
