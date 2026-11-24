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

final class PaymentSlipTest extends TestCase
{
    #[Test]
    public function it_redirects_to_a_short_lived_url_when_viewing_a_slip(): void
    {
        $this->travelTo('2026-11-24 10:00:00');
        Storage::fake('local');

        $participant = Participant::factory()->create();
        Storage::disk()->put('payment-slips/slip.pdf', 'slip contents');
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/slip.pdf']);

        $capturedOptions = [];
        $capturedExpiration = null;

        Storage::disk()->buildTemporaryUrlsUsing(
            function (string $path, \DateTimeInterface $expiration, array $options) use (&$capturedOptions, &$capturedExpiration): string {
                $capturedOptions = $options;
                $capturedExpiration = $expiration;

                return 'https://files.test/'.$path.'?token=view';
            },
        );

        $response = $this->actingAs(User::factory()->superAdmin()->create())->get(
            route('participants.payments.slip', [$participant, $payment]),
        );

        $response->assertRedirect('https://files.test/payment-slips/slip.pdf?token=view');
        $this->assertSame([], $capturedOptions);
        $this->assertSame(
            now()->addMinutes(10)->getTimestamp(),
            $capturedExpiration->getTimestamp(),
        );
    }

    #[Test]
    public function it_requests_an_attachment_response_for_slip_downloads(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();
        Storage::disk()->put('payment-slips/receipt.pdf', 'slip contents');
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/receipt.pdf']);

        $capturedOptions = [];

        Storage::disk()->buildTemporaryUrlsUsing(
            function (string $path, \DateTimeInterface $expiration, array $options) use (&$capturedOptions): string {
                $capturedOptions = $options;

                return 'https://files.test/'.$path.'?token=download';
            },
        );

        $response = $this->actingAs(User::factory()->superAdmin()->create())->get(
            route('participants.payments.slip', [$participant, $payment, 'download' => 1]),
        );

        $response->assertRedirect('https://files.test/payment-slips/receipt.pdf?token=download');
        $this->assertSame(
            ['ResponseContentDisposition' => 'attachment; filename="receipt.pdf"'],
            $capturedOptions,
        );
    }

    #[Test]
    public function it_streams_a_pdf_slip_for_the_inline_preview(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();
        Storage::disk()->put('payment-slips/slip.pdf', 'slip contents');
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/slip.pdf']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->get(
            route('participants.payments.slip.preview', [$participant, $payment]),
        );

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('cache-control', 'no-store, private');
        $response->assertStreamedContent('slip contents');
    }

    #[Test]
    public function it_returns_not_found_when_the_previewed_pdf_is_missing_from_storage(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/missing.pdf']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.payments.slip.preview', [$participant, $payment]))
            ->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_when_a_preview_is_requested_for_an_image_slip(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();
        Storage::disk()->put('payment-slips/slip.png', 'image contents');
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/slip.png']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.payments.slip.preview', [$participant, $payment]))
            ->assertNotFound();
    }

    #[Test]
    public function a_pdf_preview_cannot_be_opened_through_another_participant(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for(Participant::factory())->create(['slip_path' => 'payment-slips/slip.pdf']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.payments.slip.preview', [$participant, $payment]))
            ->assertNotFound();
    }

    #[Test]
    public function a_participant_cannot_preview_a_payment_slip(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/slip.pdf']);

        $this->actingAs(User::factory()->create())
            ->get(route('participants.payments.slip.preview', [$participant, $payment]))
            ->assertForbidden();
    }

    #[Test]
    public function a_guest_is_redirected_to_login_when_previewing_a_payment_slip(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/slip.pdf']);

        $this->get(route('participants.payments.slip.preview', [$participant, $payment]))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function it_returns_not_found_when_the_payment_has_no_slip(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create(['slip_path' => null]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.payments.slip', [$participant, $payment]))
            ->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_when_a_preview_is_requested_for_a_payment_without_a_slip(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create(['slip_path' => null]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.payments.slip.preview', [$participant, $payment]))
            ->assertNotFound();
    }

    #[Test]
    public function an_admin_cannot_preview_a_slip_from_another_batch(): void
    {
        $admin = User::factory()->admin()->create(['batch_id' => Batch::factory()]);
        $participant = Participant::factory()->for(Batch::factory())->create();
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/slip.pdf']);

        $this->actingAs($admin)
            ->get(route('participants.payments.slip.preview', [$participant, $payment]))
            ->assertNotFound();
    }

    #[Test]
    public function a_slip_cannot_be_opened_through_another_participant(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for(Participant::factory())->create(['slip_path' => 'payment-slips/slip.pdf']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.payments.slip', [$participant, $payment]))
            ->assertNotFound();
    }

    #[Test]
    public function an_admin_cannot_open_a_slip_from_another_batch(): void
    {
        $admin = User::factory()->admin()->create(['batch_id' => Batch::factory()]);
        $participant = Participant::factory()->for(Batch::factory())->create();
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/slip.pdf']);

        $this->actingAs($admin)
            ->get(route('participants.payments.slip', [$participant, $payment]))
            ->assertNotFound();
    }

    #[Test]
    public function a_participant_cannot_open_a_payment_slip(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/slip.pdf']);

        $this->actingAs(User::factory()->create())
            ->get(route('participants.payments.slip', [$participant, $payment]))
            ->assertForbidden();
    }

    #[Test]
    public function a_guest_is_redirected_to_login_when_opening_a_payment_slip(): void
    {
        $participant = Participant::factory()->create();
        $payment = Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/slip.pdf']);

        $this->get(route('participants.payments.slip', [$participant, $payment]))
            ->assertRedirect(route('login'));
    }
}
