<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_root_redirects_to_calendar(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/calendar');
    }

    public function test_calendar_page_is_accessible(): void
    {
        $response = $this->get('/calendar');

        $response->assertStatus(200);
    }

    public function test_guest_redirected_when_opening_record_form(): void
    {
        $response = $this->get(route('records.create'));

        $response->assertRedirect(route('login'));
    }
}
