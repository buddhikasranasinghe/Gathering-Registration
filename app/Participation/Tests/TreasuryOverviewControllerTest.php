<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Batch;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantRelation;
use App\Participation\Entities\Payment;
use PHPUnit\Framework\Attributes\Test;

final class TreasuryOverviewControllerTest extends TestCase
{
    #[Test]
    public function test_super_admin_views_the_global_treasury_overview(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $batch = Batch::factory()->create(['name' => 'Forty']);
        $participant = Participant::factory()->create(['batch_id' => $batch->id]);
        Payment::factory()->for($participant)->create(['amount' => '4000.00']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->get(route('treasury.index'));

        $response->assertOk();
        $response->assertSeeText('Batch breakdown');
        $response->assertSeeText('Forty');
        $response->assertSeeText('4,000.00');
        $response->assertDontSeeText('Recent payments');
        $response->assertViewHas('totals', fn (array $totals): bool => $totals['participants'] === 1
            && $totals['expected'] === 4000.0
            && $totals['collected'] === 4000.0
            && $totals['remaining'] === 0.0);
        $response->assertViewHas('batches', fn ($batches): bool => $batches->firstWhere('name', 'Forty')['settled'] === 1);
    }

    #[Test]
    public function test_main_treasurer_views_the_global_treasury_overview(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $batch = Batch::factory()->create(['name' => 'Forty']);
        $participant = Participant::factory()->create(['batch_id' => $batch->id]);
        Payment::factory()->for($participant)->create(['amount' => '4000.00']);

        $response = $this->actingAs(User::factory()->mainTreasurer()->create())->get(route('treasury.index'));

        $response->assertOk();
        $response->assertViewHas('totals', fn (array $totals): bool => $totals['collected'] === 4000.0);
    }

    #[Test]
    public function test_main_treasurer_sees_every_payment_complete_primary_participant_in_settled_tab(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $forty = Batch::factory()->create(['name' => 'Forty']);
        $thirty = Batch::factory()->create(['name' => 'Thirty']);

        $paidInForty = Participant::factory()->create(['batch_id' => $forty->id, 'name' => 'Ada Paid']);
        $paidInThirty = Participant::factory()->create(['batch_id' => $thirty->id, 'name' => 'Grace Paid']);
        $ticketed = Participant::factory()->create(['batch_id' => $forty->id, 'name' => 'Ivan Ticketed']);

        Payment::factory()->for($paidInForty)->create(['amount' => '4000.00']);
        Payment::factory()->for($paidInThirty)->create(['amount' => '4000.00']);
        Payment::factory()->for($ticketed)->create(['amount' => '4000.00']);
        IssuedTicket::factory()->for($ticketed)->create(['ticket_price' => '4000.00']);

        Participant::factory()->relationTo($paidInForty, ParticipantRelation::Spouse)->create(['name' => 'Spouse Not Listed', 'attending' => false]);

        $response = $this->actingAs(User::factory()->mainTreasurer()->create())
            ->get(route('treasury.index', ['tab' => 'settled']));

        $response->assertOk();
        $response->assertSeeText('Ada Paid');
        $response->assertSeeText('Grace Paid');
        $response->assertSeeText('Ivan Ticketed');
        $response->assertDontSeeText('Spouse Not Listed');
        $response->assertViewHas('settled', fn ($rows): bool => $rows->count() === 3
            && $rows->pluck('name')->sort()->values()->all() === ['Ada Paid', 'Grace Paid', 'Ivan Ticketed']);
    }

    #[Test]
    public function test_batch_treasurer_is_redirected_to_their_own_batch_treasury(): void
    {
        $batch = Batch::factory()->create(['name' => '40']);
        $treasurer = User::factory()->admin()->create(['batch_id' => $batch->id]);

        $response = $this->actingAs($treasurer)->get(route('treasury.index'));

        $response->assertRedirect(route('treasury.batches.show', $batch));
    }

    #[Test]
    public function test_batch_treasurer_without_a_batch_is_redirected_to_the_roster(): void
    {
        $treasurer = User::factory()->create(['role' => UserRole::Admin, 'batch_id' => null]);

        $response = $this->actingAs($treasurer)->get(route('treasury.index'));

        $response->assertRedirect(route('participants.index'));
    }

    #[Test]
    public function test_regular_user_is_forbidden_from_the_treasury_overview(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('treasury.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function test_guest_is_redirected_to_login_from_the_treasury_overview(): void
    {
        $response = $this->get(route('treasury.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function test_overview_defaults_to_the_needs_attention_tab(): void
    {
        $response = $this->actingAs(User::factory()->mainTreasurer()->create())->get(route('treasury.index'));

        $response->assertOk();
        $response->assertSeeText('Needs attention');
        $response->assertViewHas('filters', fn (array $filters): bool => $filters['activeTab'] === 'attention');
    }

    #[Test]
    public function test_settled_tab_search_limits_settled_participants(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $batch = Batch::factory()->create(['name' => 'Forty']);
        $ada = Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Ada Settled']);
        $grace = Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Grace Settled']);
        Payment::factory()->for($ada)->create(['amount' => '4000.00']);
        Payment::factory()->for($grace)->create(['amount' => '4000.00']);

        $response = $this->actingAs(User::factory()->mainTreasurer()->create())
            ->get(route('treasury.index', ['tab' => 'settled', 'settled_search' => 'Ada']));

        $response->assertOk();
        $response->assertDontSee('name="attention_batch"', false);
        $response->assertDontSee('>Status</th>', false);
        $response->assertSeeText('Ada Settled');
        $response->assertDontSeeText('Grace Settled');
        $response->assertViewHas('settled', fn ($rows): bool => $rows->count() === 1
            && $rows->first()['name'] === 'Ada Settled');
    }

    #[Test]
    public function test_needs_attention_tab_search_limits_participants_needing_attention(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $batch = Batch::factory()->create(['name' => 'Forty']);
        Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Ada Owed']);
        Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Grace Owed']);

        $response = $this->actingAs(User::factory()->mainTreasurer()->create())
            ->get(route('treasury.index', ['tab' => 'attention', 'attention_search' => 'Ada']));

        $response->assertOk();
        $response->assertSee('>Total ticket price</th>', false);
        $response->assertDontSee('>Reason</th>', false);
        $this->assertSame(1, substr_count((string) $response->getContent(), '>Refundable</th>'));
        $response->assertSeeText('Ada Owed');
        $response->assertDontSeeText('Grace Owed');
        $response->assertViewHas('attention', fn ($rows): bool => $rows->count() === 1
            && $rows->first()['name'] === 'Ada Owed');
    }

    #[Test]
    public function test_needs_attention_tab_can_filter_by_batch(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $forty = Batch::factory()->create(['name' => 'Forty']);
        $thirty = Batch::factory()->create(['name' => 'Thirty']);
        Participant::factory()->create(['batch_id' => $forty->id, 'name' => 'Forty Owed']);
        Participant::factory()->create(['batch_id' => $thirty->id, 'name' => 'Thirty Owed']);

        $response = $this->actingAs(User::factory()->mainTreasurer()->create())
            ->get(route('treasury.index', ['tab' => 'attention', 'attention_batch' => $forty->id]));

        $response->assertOk();
        $response->assertSeeText('Forty Owed');
        $response->assertDontSeeText('Thirty Owed');
        $response->assertViewHas('attention', fn ($rows): bool => $rows->count() === 1
            && $rows->first()['name'] === 'Forty Owed');
    }
}
