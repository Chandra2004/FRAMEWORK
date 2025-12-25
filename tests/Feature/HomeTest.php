<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_home_page_returns_200()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Pastikan ada teks default "THE FRAMEWORK" di halaman welcome
        $response->assertSee('THE FRAMEWORK');
    }

    public function test_404_page()
    {
        $response = $this->get('/halaman-yang-tidak-ada-12345');

        $response->assertStatus(404);
    }
}
