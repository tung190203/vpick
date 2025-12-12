<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class DomainConfigMiddleware
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();

        if ($host === 'dev.picki.vn') {

            config([
                'app.env' => 'local',
                'app.debug' => true,
                'app.url' => 'https://dev.picki.vn',

                'session.driver' => 'file',

                'database.connections.mysql.database' => 'arplus_vpick_dev',
                'database.connections.mysql.username' => 'arplus_vpick_dev',
                'database.connections.mysql.password' => 'vpick@maDoc',
                'services.google.redirect'   => 'https://dev.picki.vn/api/auth/google/callback',
                'services.facebook.redirect' => 'https://dev.picki.vn/api/auth/facebook/callback',
                'services.apple.redirect'    => 'https://dev.picki.vn/api/auth/apple/callback',

                // Frontend / API
                'app.frontend_url'     => 'https://dev.picki.vn',
                'app.api_base_url'     => 'https://dev.picki.vn/api',
                'app.redirect_success' => 'https://dev.picki.vn',
                'app.vite_base_url'   => 'https://dev.picki.vn',
            ]);
        }

        if (in_array($host, ['picki.vn','www.picki.vn'])) {

            config([
                'app.url' => 'https://picki.vn',

                'session.driver' => 'database',

                'database.connections.mysql.database' => 'arplus_vpick',
                'database.connections.mysql.username' => 'arplus_vpick',
                'database.connections.mysql.password' => 'vpick@maDoc',
                'services.google.redirect'   => 'https://picki.vn/api/auth/google/callback',
                'services.facebook.redirect' => 'https://picki.vn/api/auth/facebook/callback',
                'services.apple.redirect'    => 'https://picki.vn/api/auth/apple/callback',

                'app.frontend_url'     => 'https://picki.vn',
                'app.api_base_url'     => 'https://picki.vn/api',
                'app.redirect_success' => 'https://picki.vn',
                'app.vite_base_url'   => 'https://picki.vn',
            ]);
        }

        DB::purge('mysql');
        DB::reconnect('mysql');

        return $next($request);
    }
}
