<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_authenticated_user_can_create_and_fetch_record(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('records.store'), [
                'record_date' => '2026-05-07',
                'calendar_note' => 'shipping day',
                'ramblings' => 'worked on persistence refactor',
                'health' => ['rating' => 4, 'workout' => 'run'],
                'study' => ['rating' => 5, 'leetcode' => '2 medium'],
            ])
            ->assertRedirect(route('calendar.index'));

        $this->getJson(route('records.show', ['date' => '2026-05-07']))
            ->assertOk()
            ->assertJsonPath('date', '2026-05-07')
            ->assertJsonPath('health.rating', 4)
            ->assertJsonPath('study.rating', 5)
            ->assertJsonPath('calendar_note', 'shipping day');
    }

    public function test_range_query_returns_records_in_desc_order(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('records.store'), [
            'record_date' => '2026-05-05',
            'health' => ['rating' => 2],
            'study' => ['rating' => 3],
        ]);

        $this->actingAs($user)->post(route('records.store'), [
            'record_date' => '2026-05-06',
            'health' => ['rating' => 3],
            'study' => ['rating' => 4],
        ]);

        $this->actingAs($user)->post(route('records.store'), [
            'record_date' => '2026-05-07',
            'health' => ['rating' => 4],
            'study' => ['rating' => 5],
        ]);

        $this->getJson(route('records.range', ['start' => '2026-05-05', 'end' => '2026-05-07']))
            ->assertOk()
            ->assertJsonPath('items.0.date', '2026-05-07')
            ->assertJsonPath('items.1.date', '2026-05-06')
            ->assertJsonPath('items.2.date', '2026-05-05');
    }

    public function test_authenticated_user_can_delete_record(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('records.store'), [
            'record_date' => '2026-05-07',
            'calendar_note' => 'to be removed',
            'health' => ['rating' => 3],
            'study' => ['rating' => 3],
        ]);

        $this->actingAs($user)
            ->delete(route('records.destroy', ['date' => '2026-05-07']))
            ->assertRedirect(route('calendar.index'));

        $this->getJson(route('records.show', ['date' => '2026-05-07']))
            ->assertOk()
            ->assertJsonPath('date', '2026-05-07')
            ->assertJsonPath('calendar_note', null);
    }
}
