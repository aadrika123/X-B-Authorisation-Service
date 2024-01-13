<?php

namespace App\Pipelines\Citizen;

use Closure;

class SearchByMobile
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('mobile')) {
            return $next($request);
        }
        return $next($request)
            ->where('mobile', 'ilike', '%' . request()->input('mobile') . '%');
    }
}
