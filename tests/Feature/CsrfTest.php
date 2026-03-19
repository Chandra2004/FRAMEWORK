<?php

namespace Tests\Feature;

use Tests\TestCase;
use TheFramework\App\Http\Router;
use TheFramework\Middleware\CsrfMiddleware;
use TheFramework\App\Http\SessionManager;
use TheFramework\Helpers\Helper;

class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Start session for CSRF tests
        SessionManager::startSecureSession();
        // Register a dummy route with CSRF protection
        Router::group(['middleware' => [CsrfMiddleware::class]], function() {
            Router::post('/submit-form', function() {
                return "form submitted";
            });
        });
    }

    public function test_post_fails_without_csrf_token()
    {
        $this->post('/submit-form', ['data' => 'hello'])
             ->assertStatus(500) // Exception thrown by abort()
             ->assertSee('Token CSRF tidak valid');
    }

    public function test_post_succeeds_with_valid_csrf_token()
    {
        // Generate a token and store in session directly
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_expires'] = time() + 3600; // Valid for 1 hour
        
        // Mock the POST with the token
        $this->post('/submit-form', ['_token' => $token, 'data' => 'hello'])
             ->assertStatus(200)
             ->assertSee('form submitted');
    }

    public function test_csrf_fails_with_invalid_token()
    {
        $this->post('/submit-form', ['_token' => 'invalid-token'])
             ->assertStatus(500);
    }

    public function test_csrf_fails_when_token_is_expired()
    {
        // Generate a token and then manually expire it in session
        $token = Helper::generateCsrfToken();
        
        // Manually manipulate the TTL in session (as defined in CsrfMiddleware)
        $_SESSION['csrf_token_expires'] = time() - 100; // Expired 100s ago
        
        $this->post('/submit-form', ['_token' => $token])
             ->assertStatus(500)
             ->assertSee('Token CSRF tidak valid');
    }

    public function test_csrf_header_support()
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_expires'] = time() + 3600;
        
        $this->withHeaders(['X-CSRF-TOKEN' => $token])
             ->post('/submit-form', ['data' => 'hello'])
             ->assertStatus(200)
             ->assertSee('form submitted');
    }
}
