<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use PHPUnit\Framework\Attributes\Test;

final class ParticipantShowTest extends TestCase
{
    #[Test]
    public function when_a_super_admin_views_a_participant_then_the_details_page_is_shown(): void
    {
        $participant = Participant::factory()->create(['name' => 'Detail Person']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('Detail Person');
        $response->assertSee('Payment history');
        $response->assertSee('Issued tickets');
    }

    #[Test]
    public function when_the_details_page_is_opened_then_the_three_tabs_are_shown(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('Participant Details');
        $response->assertSee('Payment Details');
        $response->assertSee('Ticket details');
        $response->assertDontSee('Archive participant');
        $response->assertViewHas('activeTab', 'participant');
    }

    #[Test]
    public function when_a_tab_is_requested_then_it_is_selected(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'payment']));

        $response->assertOk();
        $response->assertViewHas('activeTab', 'payment');
        $response->assertSee('aria-controls="panel-payment"', false);
    }

    #[Test]
    public function when_an_unknown_tab_is_requested_then_the_participant_tab_is_selected(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'unknown']));

        $response->assertOk();
        $response->assertViewHas('activeTab', 'participant');
    }

    #[Test]
    public function when_an_admin_views_a_participant_in_its_batch_then_the_details_page_is_shown(): void
    {
        $batch = Batch::factory()->create();
        $admin = User::factory()->admin()->create(['batch_id' => $batch->id]);
        $participant = Participant::factory()->for($batch)->create(['name' => 'Batch Person']);

        $response = $this->actingAs($admin)->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('Batch Person');
    }

    #[Test]
    public function when_an_admin_views_a_participant_in_another_batch_then_it_is_not_found(): void
    {
        $adminBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $admin = User::factory()->admin()->create(['batch_id' => $adminBatch->id]);
        $participant = Participant::factory()->for($otherBatch)->create();

        $response = $this->actingAs($admin)->get(route('participants.show', $participant));

        $response->assertNotFound();
    }

    #[Test]
    public function when_a_participant_views_the_details_page_then_it_is_forbidden(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->get(route('participants.show', $participant));

        $response->assertForbidden();
    }

    #[Test]
    public function when_a_guest_views_the_details_page_then_it_redirects_to_login(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->get(route('participants.show', $participant));

        $response->assertRedirect(route('login'));
    }
}
