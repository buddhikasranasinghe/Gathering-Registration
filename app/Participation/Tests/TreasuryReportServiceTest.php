<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Participation\Entities\Batch;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantProgressStatus;
use App\Participation\Entities\ParticipantRelation;
use App\Participation\Entities\Payment;
use App\Participation\Services\TreasuryReportService;
use PHPUnit\Framework\Attributes\Test;

final class TreasuryReportServiceTest extends TestCase
{
    #[Test]
    public function test_rolls_up_relation_expected_amounts_and_payments_before_summing_totals(): void
    {
        config()->set('tickets.price_per_person', 4000);
        config()->set('tickets.price_per_child', 1000);

        $batch = Batch::factory()->create(['name' => '40']);
        $primary = Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Primary Person']);
        $spouse = Participant::factory()->relationTo($primary, ParticipantRelation::Spouse)->create(['name' => 'Spouse Person']);
        $child = Participant::factory()->relationTo($primary, ParticipantRelation::Child)->create(['name' => 'Child Person']);
        $overpaid = Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Overpaid Person']);

        Payment::factory()->for($primary)->create(['amount' => '5000.00']);
        Payment::factory()->for($spouse)->create(['amount' => '2000.00']);
        Payment::factory()->for($child)->create(['amount' => '1000.00']);
        Payment::factory()->for($overpaid)->create(['amount' => '5000.00']);

        $report = app(TreasuryReportService::class)->overview();

        $this->assertSame(2, $report['totals']['participants']);
        $this->assertSame(13000.0, $report['totals']['expected']);
        $this->assertSame(13000.0, $report['totals']['collected']);
        $this->assertSame(1000.0, $report['totals']['remaining']);
        $this->assertSame(1000.0, $report['totals']['refundable']);
        $this->assertSame(1, $report['totals']['settled']);
        $this->assertSame(1, $report['totals']['needsAttention']);
        $this->assertSame(['Overpaid Person'], $report['settled']->pluck('name')->all());
        $this->assertSame(['Primary Person'], $report['attention']->pluck('name')->all());

        $group = $report['batches']->firstWhere('name', '40');
        $this->assertSame(2, $group['participants']);
        $this->assertSame(1, $group['settled']);
        $this->assertSame(1, $group['needsAttention']);

        $rows = app(TreasuryReportService::class)->forBatch($batch)['rows'];
        $primaryRow = $rows->firstWhere('name', 'Primary Person');

        $this->assertSame(2, $rows->count());
        $this->assertSame(9000.0, $primaryRow['expected']);
        $this->assertSame(8000.0, $primaryRow['collected']);
        $this->assertSame(1000.0, $primaryRow['remaining']);
        $this->assertSame(0.0, $primaryRow['refundable']);
        $this->assertSame(ParticipantProgressStatus::PaymentIncomplete, $primaryRow['status']);
        $this->assertSame('recalculated', $primaryRow['priceSource']);
    }

    #[Test]
    public function test_uses_active_ticket_price_and_ignores_deactivated_ticket_prices(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $batch = Batch::factory()->create(['name' => '40']);
        $ticketed = Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Ticketed Person']);
        $deactivated = Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Deactivated Person']);
        $legacy = Participant::factory()->create(['batch_id' => $batch->id, 'name' => 'Legacy Person']);

        Payment::factory()->for($ticketed)->create(['amount' => '3500.00']);
        IssuedTicket::factory()->for($ticketed)->create(['ticket_price' => '3500.00']);

        Payment::factory()->for($deactivated)->create(['amount' => '4000.00']);
        IssuedTicket::factory()->for($deactivated)->deactivated()->create(['ticket_price' => '3500.00']);

        Payment::factory()->for($legacy)->create(['amount' => '4000.00']);
        IssuedTicket::factory()->for($legacy)->create(['ticket_price' => null]);

        $rows = app(TreasuryReportService::class)->forBatch($batch)['rows'];

        $ticketedRow = $rows->firstWhere('name', 'Ticketed Person');
        $this->assertSame(3500.0, $ticketedRow['expected']);
        $this->assertSame(0.0, $ticketedRow['remaining']);
        $this->assertSame(ParticipantProgressStatus::TicketIssued, $ticketedRow['status']);
        $this->assertSame('issued_ticket', $ticketedRow['priceSource']);

        $deactivatedRow = $rows->firstWhere('name', 'Deactivated Person');
        $this->assertSame(4000.0, $deactivatedRow['expected']);
        $this->assertSame(ParticipantProgressStatus::PaymentComplete, $deactivatedRow['status']);
        $this->assertSame('recalculated', $deactivatedRow['priceSource']);

        $legacyRow = $rows->firstWhere('name', 'Legacy Person');
        $this->assertSame(4000.0, $legacyRow['expected']);
        $this->assertSame('recalculated', $legacyRow['priceSource']);
    }

    #[Test]
    public function test_excludes_archived_participants_from_the_figures(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $batch = Batch::factory()->create(['name' => '40']);
        Participant::factory()->create(['batch_id' => $batch->id]);
        Payment::factory()->for(Participant::factory()->create(['batch_id' => $batch->id]))->create(['amount' => '4000.00']);

        $archived = Participant::factory()->archived()->create(['batch_id' => $batch->id]);
        Payment::factory()->for($archived)->create(['amount' => '9000.00']);

        $report = app(TreasuryReportService::class)->overview();

        $this->assertSame(2, $report['totals']['participants']);
        $this->assertSame(8000.0, $report['totals']['expected']);
        $this->assertSame(4000.0, $report['totals']['collected']);

        $group = $report['batches']->firstWhere('name', '40');
        $this->assertSame(4000.0, $group['collected']);
    }

    #[Test]
    public function test_groups_unassigned_participants_separately_from_batches(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $assigned = Participant::factory()->create(['batch_id' => Batch::factory()->state(['name' => '40'])]);
        $unassigned = Participant::factory()->create(['batch_id' => null]);

        Payment::factory()->for($assigned)->create(['amount' => '4000.00']);
        Payment::factory()->for($unassigned)->create(['amount' => '1000.00']);

        $report = app(TreasuryReportService::class)->overview();

        $this->assertSame(['40', 'Unassigned'], $report['batches']->pluck('name')->all());
        $this->assertSame(8000.0, $report['totals']['expected']);
        $this->assertSame(5000.0, $report['totals']['collected']);
        $this->assertSame(3000.0, $report['totals']['remaining']);

        $unassignedGroup = $report['batches']->firstWhere('name', 'Unassigned');
        $this->assertSame(1, $unassignedGroup['participants']);
        $this->assertSame(1000.0, $unassignedGroup['collected']);
        $this->assertSame(3000.0, $unassignedGroup['remaining']);
    }

    #[Test]
    public function test_does_not_mark_zero_expected_and_zero_paid_as_settled(): void
    {
        config()->set('tickets.price_per_person', 0);

        $batch = Batch::factory()->create(['name' => '40']);
        Participant::factory()->create(['batch_id' => $batch->id, 'attending' => false]);

        $report = app(TreasuryReportService::class)->overview();
        $rows = app(TreasuryReportService::class)->forBatch($batch)['rows'];

        $this->assertSame(0.0, $report['totals']['expected']);
        $this->assertSame(0.0, $report['totals']['collected']);
        $this->assertSame(0, $report['totals']['settled']);
        $this->assertSame(0, $report['totals']['needsAttention']);
        $this->assertSame(0, $report['settled']->count());
        $this->assertSame(ParticipantProgressStatus::NotPaid, $rows->first()['status']);
    }
}
