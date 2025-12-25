<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use TheFramework\App\Container;
use TheFramework\App\Router;

abstract class TestCase extends BaseTestCase
{
    protected $app;
    protected $obLevel;

    protected function setUp(): void
    {
        parent::setUp();
        // Simpan level buffer awal agar tidak menghapus buffer PHPUnit secara tidak sengaja
        $this->obLevel = ob_get_level();

        $this->bootApp();
    }

    protected function tearDown(): void
    {
        // Bersihkan hanya buffer yang dibuat oleh aplikasi/test kita
        while (ob_get_level() > $this->obLevel) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    protected function bootApp()
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__));
        }
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__));
        }

        // Load Helpers explicitely if needed, or rely on bootstrap
        require_once BASE_PATH . '/app/Helpers/helpers.php';

        // Load Bootstrap
        $this->app = require BASE_PATH . '/bootstrap/app.php';
    }

    /**
     * Helper untuk melakukan request simulasi (Feature Test sederhana)
     * Menggunakan Output Buffering untuk menangkap response Router.
     */
    protected function call(string $method, string $uri, array $data = [])
    {
        // Mocking Request Variables
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI'] = $uri;
        $_POST = $method === 'POST' ? $data : [];
        $_GET = $method === 'GET' ? $data : [];
        $_REQUEST = array_merge($_GET, $_POST);

        // Reset status code sebelum request
        http_response_code(200);

        ob_start();
        try {
            // Jalankan aplikasi
            Router::run();
        } catch (\Throwable $e) {
            // Tangkap exception dan render error page jika perlu, atau biarkan status ter-set
            // Jika router throw exception (misal 404), status code harusnya sudah di set sebelum throw
            // atau kita bisa set manual disini
            if ($e instanceof \Exception && $e->getCode() >= 400) {
                http_response_code($e->getCode());
            } else {
                http_response_code(500);
                echo "Exception: " . $e->getMessage();
            }
        } finally {
            $content = ob_get_clean(); // Pastikan buffer selalu dibersihkan
        }

        // Ambil status code terakhir
        $status = http_response_code();
        if ($status === false)
            $status = 200; // Fallback

        // Pass $this (TestCase instance) to TestResponse
        return new TestResponse($this, $content, $status);
    }

    protected function get($uri)
    {
        return $this->call('GET', $uri);
    }
    protected function post($uri, $data = [])
    {
        return $this->call('POST', $uri, $data);
    }
}

class TestResponse
{
    private $test; // TestCase instance
    public $content;
    public $status;

    public function __construct($test, $content, $status)
    {
        $this->test = $test;
        $this->content = $content;
        $this->status = $status;
    }

    public function assertStatus($code)
    {
        $this->test->assertEquals(
            $code,
            $this->status,
            "Expected status $code but got {$this->status}.\nResponse Snippet: " . substr($this->content, 0, 200) . "..."
        );
        return $this;
    }

    public function assertSee($text)
    {
        $this->test->assertStringContainsString(
            $text,
            $this->content,
            "Expected to see '$text' in response."
        );
        return $this;
    }
}
