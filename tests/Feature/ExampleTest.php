<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_ok(): void
    {
        $this->get('/admin/login')->assertOk();
    }
}
