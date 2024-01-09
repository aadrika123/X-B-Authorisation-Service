<?php

namespace App\Pipelines\User;

use Closure;

class SearchByRole
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('rolewise')) {
            return $next($request);
        }
        return $next($request)
            ->join('wf_roleusermaps', 'wf_roleusermaps.user_id', 'users.id')
            ->join('wf_roles', 'wf_roles.id', 'wf_roleusermaps.wf_role_id')
            ->where('wf_roles.id',  request()->input('rolewise'));
    }
}
