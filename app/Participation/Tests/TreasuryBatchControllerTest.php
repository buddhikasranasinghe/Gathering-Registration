<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use App\Participation\Entities\Payment;
use PHPUnit\Framework\Attributes\Test;

final class TreasuryBatchControllerTest extends TestCase
{
    #[Test]
    public function test_main_treasurer_views_any_batch_treasury(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $batch = Batch::factory()->create(['name' => 'Forty']);
        $participant = Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Ada Lovelace']);
        Payment::factory()->for($participant)->create(['amount' => '4000.00']);

        $response = $this->actingAs(User::factory()->mainTreasurer()->create())
            ->get(route('treasury.batches.show', ['batch' => $batch, 'tab' => 'settled']));

        $response->assertOk();
        $response->assertSeeText('Ada Lovelace');
        $response->assertSeeText('4,000.00');
        $response->assertDontSee('name="attention_batch"', false);
        $response->assertViewHas('batch', fn (Batch $viewBatch): bool => $viewBatch->is($batch));
        $response->assertViewHas('filters', fn (array $filters): bool => $filters['activeTab'] === 'settled');
        $response->assertViewHas('totals', fn (array $totals): bool => $totals['participants'] === 1
            && $totals['expected'] === 4000.0
            && $totals['collected'] === 4000.0
            && $totals['settled'] === 1);
        $response->assertViewHas('settled', fn ($rows): bool => $rows->count() === 1
            && $rows->first()['name'] === 'Ada Lovelace');
    }

    #[Test]
    public function test_batch_treasurer_views_their_own_batch_with_visible_batch_identity(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $batch = Batch::factory()->create(['name' => '40']);
        Participant::factory()->create(['batch_id' => $batch->id]);

        $response = $this->actingAs(User::factory()->admin()->create(['batch_id' => $batch->id]))
            ->get(route('treasury.batches.show', $batch));

        $response->assertOk();
        $response->assertSeeText('Treasurer — 40');
        $response->assertSeeText('Back to home');
        $response->assertDontSeeText('Back to treasury');
        $response->assertDontSeeText('Open roster');
        $response->assertDontSeeText('Recent payments');
    }

    #[Test]
    public function test_batch_treasurer_cannot_view_another_batch_treasury(): void
    {
        $treasurer = User::factory()->admin()->create();
        $otherBatch = Batch::factory()->create();

        $response = $this->actingAs($treasurer)->get(route('treasury.batches.show', $otherBatch));

        $response->assertNotFound();
    }

    #[Test]
    public function test_batch_treasurer_without_a_batch_cannot_view_a_batch_treasury(): void
    {
        $treasurer = User::factory()->create(['role' => UserRole::Admin, 'batch_id' => null]);
        $batch = Batch::factory()->create();

        $response = $this->actingAs($treasurer)->get(route('treasury.batches.show', $batch));

        $response->assertNotFound();
    }

    #[Test]
    public function test_settled_tab_search_limits_settled_participants(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $batch = Batch::factory()->create(['name' => 'Forty']);
        $ada = Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Ada Paid']);
        $grace = Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Grace Paid']);
        Payment::factory()->for($ada)->create(['amount' => '4000.00']);
        Payment::factory()->for($grace)->create(['amount' => '4000.00']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('treasury.batches.show', ['batch' => $batch, 'tab' => 'settled', 'settled_search' => 'Ada']));

        $response->assertOk();
        $response->assertDontSee('>Status</th>', false);
        $response->assertSeeText('Ada Paid');
        $response->assertDontSeeText('Grace Paid');
        $response->assertViewHas('settled', fn ($rows): bool => $rows->count() === 1
            && $rows->first()['name'] === 'Ada Paid');
    }

    #[Test]
    public function test_needs_attention_tab_search_limits_participants_needing_attention(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $batch = Batch::factory()->create(['name' => 'Forty']);
        Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Ada Owed']);
        Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Grace Owed']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('treasury.batches.show', ['batch' => $batch, 'tab' => 'attention', 'attention_search' => 'Ada']));

        $response->assertOk();
        $response->assertSee('>Total ticket price</th>', false);
        $response->assertDontSee('>Reason</th>', false);
        $response->assertDontSee('>Refundable</th>', false);
        $response->assertSeeText('Ada Owed');
        $response->assertDontSeeText('Grace Owed');
        $response->assertViewHas('attention', fn ($rows): bool => $rows->count() === 1
            && $rows->first()['name'] === 'Ada Owed');
    }

    #[Test]
    public function test_regular_user_is_forbidden_from_the_batch_treasury(): void
    {
        $batch = Batch::factory()->create();

        $response = $this->actingAs(User::factory()->create())->get(route('treasury.batches.show', $batch));

        $response->assertForbidden();
    }

    #[Test]
    public function test_guest_is_redirected_to_login_from_the_batch_treasury(): void
    {
        $batch = Batch::factory()->create();

        $response = $this->get(route('treasury.batches.show', $batch));

        $response->assertRedirect(route('login'));
    }
}
