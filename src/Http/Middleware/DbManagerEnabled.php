<?php

namespace MahmoudAbdElhalim\DbManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DbManagerEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('db-manager.enabled', true)) {
            abort(404);
        }

        return $next($request);
    }
}
