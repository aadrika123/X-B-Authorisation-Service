<?php

namespace App\Pipelines\Otp;

use Closure;

class SearchByOtp
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('Otp')) {
            return $next($request);
        }
        return $next($request)
            ->where('otp', request()->input('Otp'));
    }
}
