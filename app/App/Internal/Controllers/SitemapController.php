<?php

namespace TheFramework\App\Internal\Controllers;

use TheFramework\App\Http\Router;
use TheFramework\App\Core\Config;

/**
 * SitemapController - Framework Internal
 * Otomatisasi pembuatan Sitemap XML berdasarkan rute aplikasi.
 */
class SitemapController
{
    /**
     * Generate Sitemap XML automatically based on registered routes.
     */
    public function index()
    {
        $routes = Router::getRouteDefinitions();

        $appUrl = Config::get('APP_URL');
        if (empty($appUrl) || $appUrl === 'http://localhost') {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $appUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];
        }
        $baseUrl = rtrim($appUrl, '/');

        header("Content-Type: application/xml; charset=utf-8");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        $uniquePaths = [];

        foreach ($routes as $route) {
            $path = $route['path'];

            if ($route['method'] !== 'GET')
                continue;
            if (str_starts_with($path, '/system-panel'))
                continue;
            if (str_starts_with($path, '/api'))
                continue;

            // Hindari rute dinamis (Sitemap statis)
            if (preg_match('/[\{\}\(\)\*]/', $path))
                continue;

            if (in_array($path, $uniquePaths))
                continue;
            $uniquePaths[] = $path;

            $xml .= '    <url>' . PHP_EOL;
            $xml .= '        <loc>' . htmlspecialchars($baseUrl . $path) . '</loc>' . PHP_EOL;
            $xml .= '        <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
            $xml .= '        <changefreq>daily</changefreq>' . PHP_EOL;
            $xml .= '        <priority>' . ($path === '/' ? '1.0' : '0.8') . '</priority>' . PHP_EOL;
            $xml .= '    </url>' . PHP_EOL;
        }

        // 2. DYNAMIC CONTENT: POSTS (Premium Feature)
        // If the Post model exists, scan entries automatically
        $postModel = "\\TheFramework\\Models\\Post";
        if (class_exists($postModel)) {
            try {
                $posts = (new $postModel)->query()->latest()->get();
                foreach ($posts as $post) {
                    $val = is_object($post) ? ($post->slug ?? $post->uid ?? $post->id) : ($post['slug'] ?? $post['uid'] ?? $post['id'] ?? null);
                    if (!$val)
                        continue;

                    $xml .= '    <url>' . PHP_EOL;
                    $xml .= '        <loc>' . htmlspecialchars($baseUrl . '/blog/' . $val) . '</loc>' . PHP_EOL;
                    $xml .= '        <lastmod>' . (is_object($post) ? ($post->updated_at ?? date('Y-m-d')) : ($post['updated_at'] ?? date('Y-m-d'))) . '</lastmod>' . PHP_EOL;
                    $xml .= '        <changefreq>weekly</changefreq>' . PHP_EOL;
                    $xml .= '        <priority>0.6</priority>' . PHP_EOL;
                    $xml .= '    </url>' . PHP_EOL;
                }
            } catch (\Exception $e) {
                // Silently skip if query fails (table doesn't exist etc)
            }
        }

        $xml .= '</urlset>';

        echo $xml;
        exit;
    }
}
