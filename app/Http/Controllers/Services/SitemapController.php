<?php

namespace TheFramework\Http\Controllers\Services;

use TheFramework\App\Router;
use TheFramework\App\Config;

class SitemapController
{
    /**
     * Generate Sitemap XML automatically based on registered routes.
     * 
     * @return void
     */
    public function index()
    {
        $routes = Router::getRouteDefinitions();

        // Dapatkan Base URL dari .env atau construct manual
        $appUrl = Config::get('APP_URL');
        if (empty($appUrl) || $appUrl === 'http://localhost') {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $appUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];
        }
        $baseUrl = rtrim($appUrl, '/');

        // Header XML
        header("Content-Type: application/xml; charset=utf-8");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        $uniquePaths = [];

        foreach ($routes as $route) {
            $path = $route['path'];

            // 1. Hanya method GET
            if ($route['method'] !== 'GET') {
                continue;
            }

            // 2. Filter System & API Routes
            if (str_starts_with($path, '/_system'))
                continue;
            if (str_starts_with($path, '/api'))
                continue;

            // 3. Filter Dynamic Routes (Parameter {id}, (.*), dll)
            // Sitemap otomatis hanya bisa handle static routes.
            // Untuk dynamic routes (misal /blog/{slug}), user harus buat manual logicnya nanti.
            if (str_contains($path, '{') || str_contains($path, '}'))
                continue;
            if (str_contains($path, '(') || str_contains($path, ')'))
                continue;
            if (str_contains($path, '*'))
                continue;

            // 4. Hindari duplikat
            if (in_array($path, $uniquePaths)) {
                continue;
            }
            $uniquePaths[] = $path;

            // Generate Entry
            $xml .= '    <url>' . PHP_EOL;
            $xml .= '        <loc>' . htmlspecialchars($baseUrl . $path) . '</loc>' . PHP_EOL;
            $xml .= '        <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
            $xml .= '        <changefreq>daily</changefreq>' . PHP_EOL;
            $xml .= '        <priority>' . ($path === '/' ? '1.0' : '0.8') . '</priority>' . PHP_EOL;
            $xml .= '    </url>' . PHP_EOL;
        }

        // 5. Dynamic Content: Blog Posts (Contoh Implementasi)
        if (class_exists('\\TheFramework\\Models\\Post')) {
            try {
                $posts = \TheFramework\Models\Post::all();
                foreach ($posts as $post) {
                    $xml .= '    <url>' . PHP_EOL;
                    $xml .= '        <loc>' . htmlspecialchars($baseUrl . '/blog/' . ($post->slug ?? $post->id)) . '</loc>' . PHP_EOL;
                    $xml .= '        <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
                    $xml .= '        <changefreq>weekly</changefreq>' . PHP_EOL;
                    $xml .= '        <priority>0.6</priority>' . PHP_EOL;
                    $xml .= '    </url>' . PHP_EOL;
                }
            } catch (\Exception $e) {
                // Silently skip if table doesn't exist or DB error
            }
        }

        $xml .= '</urlset>';

        echo $xml;
        exit;
    }
}
