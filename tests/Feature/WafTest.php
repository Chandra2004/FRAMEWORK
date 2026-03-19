<?php

namespace Tests\Feature;

use Tests\TestCase;
use TheFramework\App\Http\Router;
use TheFramework\Middleware\WAFMiddleware;

class WafTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Register a dummy route with WAF protection
        Router::group(['middleware' => [WAFMiddleware::class]], function() {
            Router::get('/secure-route', function() {
                return "authorized";
            });
            Router::post('/secure-route', function() {
                return "authorized post";
            });
        });
    }

    public function test_waf_blocks_simple_sqli()
    {
        // Try simple SQLi pattern
        $this->get('/secure-route?id=1 OR 1=1')
             ->assertStatus(403)
             ->assertSee('"success":false')
             ->assertSee('"error":"Security Alert"');
    }

    public function test_waf_blocks_comment_based_sqli()
    {
        // Try SQLi with comments that we should normalize
        $this->get('/secure-route?id=1 union/**/select')
             ->assertStatus(403)
             ->assertSee('"success":false')
             ->assertSee('"error":"Security Alert"');
    }

    public function test_waf_blocks_simple_xss()
    {
        // Try simple XSS pattern
        $this->get('/secure-route?q=<script>alert(1)</script>')
             ->assertStatus(403)
             ->assertSee('"success":false')
             ->assertSee('"error":"Security Alert"');
    }

    public function test_waf_allows_legitimate_queries()
    {
        // Try legitimate search query
        $this->get('/secure-route?q=search term')
             ->assertStatus(200)
             ->assertSee('authorized');

        // Try legitimate ID
        $this->get('/secure-route?id=123')
             ->assertStatus(200)
             ->assertSee('authorized');
    }

    public function test_waf_blocks_uri_based_xss()
    {
        // Try XSS in query parameter
        $this->get('/secure-route?q=<script>')
             ->assertStatus(403);
    }

    public function test_waf_allows_encoded_legitimate_data()
    {
        $this->get('/secure-route?data=value%20with%20space')
             ->assertStatus(200)
             ->assertSee('authorized');
    }
}
