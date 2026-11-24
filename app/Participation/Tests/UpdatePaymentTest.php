<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use App\Participation\Entities\Payment;
use App\Participation\Entities\PaymentMethod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

final class UpdatePaymentTest extends TestCase
{
    #[Test]
    public function it_updates_every_editable_field_and_tracks_the_editor(): void
    {
        $participant = Participant::factory()->create();
        $originalAdmin = User::factory()->superAdmin()->create(['name' => 'Original Admin']);
        $editor = User::factory()->superAdmin()->create(['name' => 'Editor Admin']);
        $payment = Payment::factory()->for($participant)->create([
            'amount' => '1000.00',
            'method' => PaymentMethod::Cash,
            'paid_at' => '2026-11-20',
            'recorded_by' => $originalAdmin->getKey(),
            'recorded_by_name' => $originalAdmin->name,
        ]);

        $response = $this->actingAs($editor)->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'amount' => '2500.75',
                'method' => 'card',
                'paid_at' => '2026-11-22',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Payment updated.');

        $payment->refresh();

        $this->assertSame('2500.75', $payment->amount);
        $this->assertSame(PaymentMethod::Card, $payment->method);
        $this->assertSame('2026-11-22', $payment->paid_at->toDateString());
        $this->assertSame($originalAdmin->getKey(), $payment->recorded_by);
        $this->assertSame('Original Admin', $payment->recorded_by_name);
        $this->assertSame($editor->getKey(), $payment->updated_by);
        $this->assertSame('Editor Admin', $payment->updated_by_name);

        $this->actingAs($editor)
            ->get(route('participants.show', [$participant, 'tab' => 'payment']))
            ->assertSee('Last updated by', false)
            ->assertSee('Editor Admin');
    }

    #[Test]
    public function it_recalculates_the_payment_totals_after_an_amount_is_corrected(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['attending' => true]);
        $payment = Payment::factory()->for($participant)->create(['amount' => '1000.00']);

        $this->actingAs(User::factory()->superAdmin()->create())->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'amount' => '2500.00',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
            ],
        )->assertRedirect();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'payment']))
            ->assertViewHas('summary', fn (array $summary): bool => $summary['ticketPrice'] === 4000.0
                && $summary['totalPaid'] === 2500.0
                && $summary['remaining'] === 1500.0
                && $summary['refund'] === 0.0);
    }

    #[Test]
    public function it_adds_a_slip_when_one_was_not_uploaded_before(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create(['slip_path' => null]);

        $this->actingAs(User::factory()->superAdmin()->create())->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
                'slip' => UploadedFile::fake()->create('added-slip.pdf', 100, 'application/pdf'),
            ],
        )->assertRedirect();

        $payment->refresh();

        $this->assertStringStartsWith('payment-slips/', $payment->slip_path);
        Storage::disk()->assertExists($payment->slip_path);
    }

    #[Test]
    public function it_deletes_the_previous_slip_when_it_is_replaced(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();
        Storage::disk()->put('payment-slips/old.pdf', 'old slip');
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/old.pdf']);

        $this->actingAs(User::factory()->superAdmin()->create())->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
                'slip' => UploadedFile::fake()->create('new-slip.pdf', 100, 'application/pdf'),
            ],
        )->assertRedirect();

        $payment->refresh();

        $this->assertNotSame('payment-slips/old.pdf', $payment->slip_path);
        Storage::disk()->assertMissing('payment-slips/old.pdf');
        Storage::disk()->assertExists($payment->slip_path);
    }

    #[Test]
    public function it_keeps_the_existing_slip_when_no_new_file_is_uploaded(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();
        Storage::disk()->put('payment-slips/existing.pdf', 'existing slip');
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/existing.pdf']);

        $this->actingAs(User::factory()->superAdmin()->create())->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'amount' => '1800',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
            ],
        )->assertRedirect();

        $this->assertSame('payment-slips/existing.pdf', $payment->refresh()->slip_path);
        Storage::disk()->assertExists('payment-slips/existing.pdf');
    }

    #[Test]
    public function it_removes_the_slip_and_deletes_the_stored_file_when_requested(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();
        Storage::disk()->put('payment-slips/remove-me.pdf', 'old slip');
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/remove-me.pdf']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
                'remove_slip' => '1',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertNull($payment->refresh()->slip_path);
        Storage::disk()->assertMissing('payment-slips/remove-me.pdf');
    }

    #[Test]
    public function it_keeps_the_current_slip_when_the_uploaded_replacement_is_invalid(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();
        Storage::disk()->put('payment-slips/existing.pdf', 'existing slip');
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/existing.pdf']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
                'slip' => UploadedFile::fake()->create('replacement.gif', 100, 'image/gif'),
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('slip');

        $this->assertSame('payment-slips/existing.pdf', $payment->refresh()->slip_path);
        Storage::disk()->assertExists('payment-slips/existing.pdf');
    }

    #[Test]
    public function it_rejects_invalid_changes_without_modifying_the_payment(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create(['amount' => '1000.00']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'payment_id' => $payment->getKey(),
                'amount' => '0',
                'method' => 'not-a-method',
                'paid_at' => '20-11-2026',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors(['amount', 'method', 'paid_at']);

        $payment->refresh();

        $this->assertSame('1000.00', $payment->amount);
        $this->assertNull($payment->updated_by);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'payment']))
            ->assertSee('data-payment-open-error="1"', false);
    }

    #[Test]
    public function a_payment_cannot_be_updated_through_another_participant(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for(Participant::factory())->create();

        $this->actingAs(User::factory()->superAdmin()->create())->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
            ],
        )->assertNotFound();

        $this->assertNull($payment->refresh()->updated_by);
    }

    #[Test]
    public function an_admin_cannot_update_a_payment_outside_its_batch(): void
    {
        $admin = User::factory()->admin()->create(['batch_id' => Batch::factory()]);
        $participant = Participant::factory()->for(Batch::factory())->create();
        $payment = Payment::factory()->for($participant)->create();

        $this->actingAs($admin)->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
            ],
        )->assertNotFound();

        $this->assertNull($payment->refresh()->updated_by);
    }

    #[Test]
    public function a_participant_cannot_update_a_payment(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create();

        $this->actingAs(User::factory()->create())->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
            ],
        )->assertForbidden();

        $this->assertNull($payment->refresh()->updated_by);
    }

    #[Test]
    public function a_guest_is_redirected_to_login_when_updating_a_payment(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create();

        $this->patch(
            route('participants.payments.update', [$participant, $payment]),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
            ],
        )->assertRedirect(route('login'));

        $this->assertNull($payment->refresh()->updated_by);
    }
}
