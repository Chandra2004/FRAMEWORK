<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use TheFramework\App\Core\Container;
use TheFramework\App\Http\Router;
use TheFramework\App\Database\Database;
use Tests\Traits\DatabaseTransactions;

abstract class TestCase extends BaseTestCase
{
    protected $app;
    protected $obLevel;
    protected $defaultHeaders = [];

    protected function setUp(): void
    {
        parent::setUp();
        // Simpan level buffer awal agar tidak menghapus buffer PHPUnit secara tidak sengaja
        $this->obLevel = ob_get_level();

        Router::clearRoutes();
        $this->bootApp();

        if (method_exists($this, 'beginDatabaseTransaction')) {
            call_user_func([$this, 'beginDatabaseTransaction']);
        }
    }

    protected function tearDown(): void
    {
        if (method_exists($this, 'rollBackDatabaseTransaction')) {
            call_user_func([$this, 'rollBackDatabaseTransaction']);
        }

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

        // Force ENV to testing
        $_ENV['APP_ENV'] = 'testing';
        // Atau jika pakai Config class
        if (class_exists('\\TheFramework\\App\\Config')) {
            // Kita bisa paksa Config meload .env.testing nanti
        }

        // Load Helpers explicitely if needed, or rely on bootstrap
        require_once BASE_PATH . '/app/Helpers/helpers.php';

        // Load Bootstrap
        $this->app = require BASE_PATH . '/bootstrap/app.php';

        // Load Routes
        if (file_exists(BASE_PATH . '/routes/web.php')) {
            require BASE_PATH . '/routes/web.php';
        }
        if (file_exists(BASE_PATH . '/routes/api.php')) {
            require BASE_PATH . '/routes/api.php';
        }
        if (file_exists(BASE_PATH . '/app/App/Internal/Controllers/_system/routes.php')) {
            require BASE_PATH . '/app/App/Internal/Controllers/_system/routes.php';
        }
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
        
        // Parse URI for path() vs query()
        $parts = parse_url($uri);
        $_SERVER['QUERY_STRING'] = $parts['query'] ?? '';
        
        if (strtoupper($method) === 'GET') {
            $_GET = $data;
            $_POST = [];
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $queryData);
                $_GET = array_merge($queryData, $_GET);
            }
        } else {
            $_GET = [];
            $_POST = $data;
        }
        $_REQUEST = array_merge($_GET, $_POST);

        // Inject Headers
        foreach ($this->defaultHeaders as $name => $val) {
            $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $_SERVER[$serverKey] = $val;
            if (strtolower($name) === 'content-type') {
                $_SERVER['CONTENT_TYPE'] = $val;
            }
        }

        // 🚀 CRITICAL: Reset Request in Container to ensure fresh input parsing
        \TheFramework\App\Core\Container::getInstance()->forgetInstance(\TheFramework\App\Http\Request::class);

        // Reset status code sebelum request
        http_response_code(200);

        // Setup Profiler
        $startTime = microtime(true);
        $initialQueryCount = count(Database::getInstance()->getQueryLog());

        ob_start();
        try {
            // Jalankan aplikasi
            Router::run();
        } catch (\Throwable $e) {
            if ($e instanceof \Exception && (str_starts_with($e->getMessage(), 'REDIRECT:') || str_contains($e->getMessage(), 'JSON_'))) {
                http_response_code($e->getCode() ?: 302);
                echo $e->getMessage();
            } elseif ($e instanceof \Exception && $e->getCode() >= 400) {
                http_response_code($e->getCode());
            } else {
                http_response_code(500);
                echo "Exception: " . $e->getMessage();
            }
        } finally {
            $content = ob_get_clean(); // Pastikan buffer selalu dibersihkan
            $executionTimeMs = (microtime(true) - $startTime) * 1000;
            $finalQueryCount = count(Database::getInstance()->getQueryLog());
            $queriesExecuted = max(0, $finalQueryCount - $initialQueryCount);
        }

        // Ambil status code terakhir
        $status = http_response_code();
        if ($status === false)
            $status = 200; // Fallback

        // Pass $this (TestCase instance) to TestResponse
        return new TestResponse($this, $content, $status, $executionTimeMs, $queriesExecuted);
    }

    protected function get($uri)
    {
        return $this->call('GET', $uri);
    }
    protected function post($uri, $data = [])
    {
        return $this->call('POST', $uri, $data);
    }

    protected function put($uri, $data = [])
    {
        return $this->call('PUT', $uri, $data);
    }

    protected function patch($uri, $data = [])
    {
        return $this->call('PATCH', $uri, $data);
    }

    protected function delete($uri, $data = [])
    {
        return $this->call('DELETE', $uri, $data);
    }

    protected function json($method, $uri, $data = [])
    {
        $this->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json']);
        return $this->call($method, $uri, $data);
    }

    public function withHeaders(array $headers)
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    public function withSession(array $data)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
        return $this;
    }

    public function actingAs($user)
    {
        $this->withSession(['user_id' => is_object($user) ? current((array)$user) : $user]);
        return $this;
    }

    public function assertDatabaseHas(string $table, array $data)
    {
        $db = Database::getInstance();
        $query = "SELECT COUNT(*) FROM `{$table}` WHERE ";
        $conditions = [];
        foreach ($data as $col => $val) {
            $conditions[] = "`{$col}` = " . (is_null($val) ? "NULL" : "'".addslashes((string)$val)."'");
        }
        $query .= implode(' AND ', $conditions);
        
        $countResult = $db->query($query)->resultSet();
        $count = reset($countResult[0]) ?? 0;
        
        $this->assertTrue($count > 0, "Failed asserting that database has row in table [{$table}]");
        return $this;
    }

    public function assertDatabaseMissing(string $table, array $data)
    {
        $db = Database::getInstance();
        $query = "SELECT COUNT(*) FROM `{$table}` WHERE ";
        $conditions = [];
        foreach ($data as $col => $val) {
            $conditions[] = "`{$col}` = " . (is_null($val) ? "NULL" : "'".addslashes((string)$val)."'");
        }
        $query .= implode(' AND ', $conditions);
        
        $countResult = $db->query($query)->resultSet();
        $count = reset($countResult[0]) ?? 0;
        
        $this->assertTrue($count == 0, "Failed asserting that database is missing row in table [{$table}]");
        return $this;
    }

    public function assertDatabaseCount(string $table, int $expected)
    {
        $db = Database::getInstance();
        $countResult = $db->query("SELECT COUNT(*) FROM `{$table}`")->resultSet();
        $count = reset($countResult[0]) ?? 0;
        
        $this->assertEquals($expected, $count, "Failed asserting that table [{$table}] has {$expected} rows.");
        return $this;
    }
}

class TestResponse
{
    private $test; // TestCase instance
    public $content;
    public $status;
    public $executionTime;
    public $queryCount;

    public function __construct($test, $content, $status, $executionTime = 0, $queryCount = 0)
    {
        $this->test = $test;
        $this->content = $content;
        $this->status = $status;
        $this->executionTime = $executionTime;
        $this->queryCount = $queryCount;
    }

    // ==========================================
    // ⚡ EXCLUSIVE FEATURE (BEYOND LARAVEL)
    // ==========================================

    public function assertExecutionTimeUnder(int $milliseconds)
    {
        $this->test->assertLessThan(
            $milliseconds, 
            $this->executionTime, 
            "Expected response to take less than {$milliseconds}ms, but took " . round($this->executionTime, 2) . "ms."
        );
        return $this;
    }

    public function assertQueryCount(int $expected)
    {
        $this->test->assertEquals(
            $expected, 
            $this->queryCount, 
            "Expected exactly {$expected} database queries, but {$this->queryCount} were executed."
        );
        return $this;
    }

    public function assertQueriesLessThan(int $maxQueries)
    {
        $this->test->assertLessThan(
            $maxQueries, 
            $this->queryCount, 
            "Expected less than {$maxQueries} database queries, but {$this->queryCount} were executed. Possible N+1 Query Detected!"
        );
        return $this;
    }

    // ==========================================

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

    public function assertSuccessful() { $this->test->assertTrue($this->status >= 200 && $this->status < 300, "Expected successful response, got {$this->status}"); return $this; }
    public function assertNotFound() { $this->assertStatus(404); return $this; }
    public function assertForbidden() { $this->assertStatus(403); return $this; }
    public function assertUnauthorized() { $this->assertStatus(401); return $this; }

    public function assertRedirect($uri = null)
    {
        $this->test->assertTrue($this->status >= 300 && $this->status < 400, "Response status {$this->status} is not a redirect.");
        if ($uri) {
            if (str_starts_with($this->content, 'REDIRECT:')) {
                $this->test->assertStringContainsString((string)$uri, $this->content, "Expected redirect to {$uri}");
            }
        }
        return $this;
    }

    public function assertDontSee($text)
    {
        $this->test->assertStringNotContainsString(
            (string)$text,
            $this->content,
            "Expected not to see '$text' in response."
        );
        return $this;
    }

    public function assertJson(array $data)
    {
        $json = json_decode($this->content, true);

        $this->test->assertIsArray($json, "Response was not JSON: " . substr($this->content, 0, 100));

        foreach ($data as $key => $value) {
            $this->test->assertArrayHasKey($key, $json);
            $this->test->assertEquals($value, $json[$key]);
        }
        return $this;
    }
}
