<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestMetaPreviewCommand extends Command
{
    protected $signature = 'meta:test-preview {url?}';
    protected $description = 'Test Link Card Preview - simulate crawler request and show og:meta';

    public function handle(): int
    {
        $baseUrl = rtrim(config('app.frontend_url') ?: config('app.url'), '/');
        $defaultPath = '/clubs/' . (\App\Models\Club\Club::first()?->id ?? 1);
        $url = $this->argument('url') ?? $baseUrl . $defaultPath;
        $this->info("Testing: {$url}");
        $this->info('User-Agent: facebookexternalhit/1.1');
        $this->newLine();

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
            ])->timeout(10)->get($url);

            $html = $response->body();

            if (!str_contains($html, 'og:title')) {
                $this->warn('WARNING: No og:title found - crawler may be getting SPA instead of meta HTML');
                $this->warn('Ensure: 1) Server is running, 2) URL is correct, 3) Club/entity exists');
            } else {
                $this->info('SUCCESS: og:meta tags found!');
                preg_match_all('/<meta[^>]+property="og:([^"]+)"[^>]+content="([^"]*)"/', $html, $matches);
                if (!empty($matches[0])) {
                    $this->table(['og:property', 'content'], array_map(null, $matches[1], $matches[2]));
                }
            }

            $this->newLine();
            $this->line('First 1500 chars of response:');
            $this->line(substr($html, 0, 1500));

            return 0;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->warn('Make sure the server is running: php artisan serve (or your web server)');

            return 1;
        }
    }
}
