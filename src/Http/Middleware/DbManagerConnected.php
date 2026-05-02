<?php

namespace MahmoudAbdElhalim\DbManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DbManagerConnected
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = config('db-manager.connection_mode', 'laravel');

        if ($mode === 'custom' && ! session('db_manager_connection')) {
            return redirect()->route('db-manager.connect')
                ->with('error', 'Please connect to a MySQL server first.');
        }

        return $next($request);
    }
}
