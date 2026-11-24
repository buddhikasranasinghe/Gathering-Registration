<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use App\Participation\Entities\Payment;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

final class DeletePaymentTest extends TestCase
{
    #[Test]
    public function it_deletes_the_payment_and_its_slip_file(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();
        Storage::disk()->put('payment-slips/receipt.pdf', 'slip contents');
        $payment = Payment::factory()->for($participant)->create([
            'amount' => '1000.00',
            'slip_path' => 'payment-slips/receipt.pdf',
        ]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->delete(
            route('participants.payments.destroy', [$participant, $payment]),
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Payment deleted.');
        $this->assertNull($payment->fresh());
        Storage::disk()->assertMissing('payment-slips/receipt.pdf');
    }

    #[Test]
    public function it_recalculates_the_payment_totals_after_a_payment_is_deleted(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['attending' => true]);
        $deletedPayment = Payment::factory()->for($participant)->create(['amount' => '1500.00']);
        Payment::factory()->for($participant)->create(['amount' => '500.00']);

        $this->actingAs(User::factory()->superAdmin()->create())->delete(
            route('participants.payments.destroy', [$participant, $deletedPayment]),
        )->assertRedirect();

        $this->assertNull($deletedPayment->fresh());

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'payment']))
            ->assertViewHas('summary', fn (array $summary): bool => $summary['ticketPrice'] === 4000.0
                && $summary['totalPaid'] === 500.0
                && $summary['remaining'] === 3500.0
                && $summary['refund'] === 0.0);
    }

    #[Test]
    public function a_payment_cannot_be_deleted_through_another_participant(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for(Participant::factory())->create();

        $this->actingAs(User::factory()->superAdmin()->create())->delete(
            route('participants.payments.destroy', [$participant, $payment]),
        )->assertNotFound();

        $this->assertNotNull($payment->fresh());
    }

    #[Test]
    public function an_admin_cannot_delete_a_payment_outside_their_batch(): void
    {
        $admin = User::factory()->admin()->create(['batch_id' => Batch::factory()]);
        $participant = Participant::factory()->for(Batch::factory())->create();
        $payment = Payment::factory()->for($participant)->create();

        $this->actingAs($admin)->delete(
            route('participants.payments.destroy', [$participant, $payment]),
        )->assertNotFound();

        $this->assertNotNull($payment->fresh());
    }

    #[Test]
    public function an_unprivileged_user_cannot_delete_a_payment(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create();

        $this->actingAs(User::factory()->create())->delete(
            route('participants.payments.destroy', [$participant, $payment]),
        )->assertForbidden();

        $this->assertNotNull($payment->fresh());
    }

    #[Test]
    public function a_guest_is_redirected_to_login_when_deleting_a_payment(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create();

        $this->delete(route('participants.payments.destroy', [$participant, $payment]))
            ->assertRedirect(route('login'));

        $this->assertNotNull($payment->fresh());
    }

    #[Test]
    public function it_shows_the_delete_control_and_confirmation_dialog(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'payment']));

        $response->assertOk();
        $response->assertSee('data-payment-delete', false);
        $response->assertSee('data-payment-delete-confirm', false);
        $response->assertSee('data-delete-route-template', false);
        $response->assertSee('Delete payment', false);
        $response->assertSee('Cancel');
    }
}
