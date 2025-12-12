<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $host = request()->getHost();

        /**
         * ================================
         *  PRODUCTION — picki.vn
         * ================================
         */
        if (in_array($host, ['picki.vn', 'www.picki.vn'])) {

            config([
                'app.url' => 'https://picki.vn',

                'database.connections.mysql.host'     => '127.0.0.1',
                'database.connections.mysql.port'     => '3306',
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

        /**
         * ================================
         *  DEVELOPMENT — dev.picki.vn
         * ================================
         */
        if ($host === 'dev.picki.vn') {

            config([
                'app.env' => 'local',
                'app.debug' => true,
                'app.url' => 'https://dev.picki.vn',

                // DB clone dev
                'database.connections.mysql.host'     => '127.0.0.1',
                'database.connections.mysql.port'     => '3306',
                'database.connections.mysql.database' => 'arplus_vpick_dev',
                'database.connections.mysql.username' => 'arplus_vpick_dev',
                'database.connections.mysql.password' => 'vpick@maDoc',

                // OAuth redirect DEV
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

        DB::reconnect();
    }
}
