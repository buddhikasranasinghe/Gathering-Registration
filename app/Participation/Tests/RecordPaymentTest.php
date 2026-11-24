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
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Imagick;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

final class RecordPaymentTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_stores_the_payment_amount_method_date_and_actor(): void
    {
        $participant = Participant::factory()->create();
        $admin = User::factory()->superAdmin()->create(['name' => 'Buddhika']);

        $response = $this->actingAs($admin)->post(
            route('participants.payments.store', $participant),
            [
                'amount' => '1500.50',
                'method' => 'bank_transfer',
                'paid_at' => '2026-11-20',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Payment recorded.');

        $payment = Payment::query()->sole();

        $this->assertSame($participant->getKey(), $payment->participant_id);
        $this->assertSame('1500.50', $payment->amount);
        $this->assertSame(PaymentMethod::BankTransfer, $payment->method);
        $this->assertSame('2026-11-20', $payment->paid_at->toDateString());
        $this->assertSame($admin->getKey(), $payment->recorded_by);
        $this->assertSame('Buddhika', $payment->recorded_by_name);
        $this->assertNull($payment->slip_path);
    }

    #[Test]
    public function it_stores_an_uploaded_slip_on_the_default_disk(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.payments.store', $participant),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
                'slip' => UploadedFile::fake()->create('slip.pdf', 100, 'application/pdf'),
            ],
        )->assertRedirect();

        $payment = Payment::query()->sole();

        $this->assertStringStartsWith('payment-slips/', $payment->slip_path);
        Storage::disk()->assertExists($payment->slip_path);
    }

    #[DataProvider('supportedSlipFormats')]
    #[Test]
    public function it_accepts_supported_slip_formats(string $name, string $mimeType): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.payments.store', $participant),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
                'slip' => UploadedFile::fake()->create($name, 100, $mimeType),
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payment = Payment::query()->sole();

        Storage::disk()->assertExists($payment->slip_path);
    }

    #[DataProvider('heicSlipNames')]
    #[Test]
    public function it_converts_heic_and_heif_slips_to_jpeg(string $name): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.payments.store', $participant),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
                'slip' => $this->heicUpload($name),
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payment = Payment::query()->sole();

        $this->assertStringStartsWith('payment-slips/', $payment->slip_path);
        $this->assertStringEndsWith('.jpg', $payment->slip_path);
        $this->assertSame('image/jpeg', Storage::disk()->mimeType($payment->slip_path));
        $this->assertSame(1, count(Storage::disk()->allFiles('payment-slips')));
    }

    #[Test]
    public function it_rejects_an_unsupported_slip_format(): void
    {
        Storage::fake('local');

        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.payments.store', $participant),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
                'slip' => UploadedFile::fake()->create('slip.gif', 100, 'image/gif'),
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('slip');
        $this->assertSame(0, Payment::query()->count());
    }

    #[Test]
    public function it_shows_the_payment_in_history_with_a_date_and_the_added_by_name(): void
    {
        $participant = Participant::factory()->create();
        $admin = User::factory()->superAdmin()->create(['name' => 'Buddhika']);

        $this->actingAs($admin)->post(
            route('participants.payments.store', $participant),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
            ],
        )->assertRedirect();

        $response = $this->actingAs($admin)->get(
            route('participants.show', [$participant, 'tab' => 'payment']),
        );

        $response->assertOk();
        $response->assertSee('20 Nov 2026', false);
        $response->assertDontSee('20 Nov 2026,', false);
        $response->assertSee('Buddhika');
        $response->assertSee('Added by');
        $response->assertViewHas('summary', fn (array $summary): bool => $summary['totalPaid'] === 1500.0);
    }

    #[Test]
    public function it_renders_a_date_only_form_without_a_note_field(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'payment']));

        $response->assertOk();
        $response->assertSee('Paid on');
        $response->assertSee('type="date"', false);
        $response->assertDontSee('name="note"', false);
        $response->assertDontSee('>Note<', false);
        $this->assertFalse(Schema::hasColumn('payments', 'note'));
    }

    #[Test]
    public function it_rejects_an_empty_payment_payload(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.payments.store', $participant),
            [],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors(['amount', 'method', 'paid_at']);
        $this->assertSame(0, Payment::query()->count());
    }

    #[Test]
    public function it_rejects_a_paid_date_that_includes_a_time(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.payments.store', $participant),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20 12:30',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('paid_at');
        $this->assertSame(0, Payment::query()->count());
    }

    #[Test]
    public function an_admin_cannot_record_a_payment_for_another_batch(): void
    {
        $admin = User::factory()->admin()->create(['batch_id' => Batch::factory()]);
        $participant = Participant::factory()->for(Batch::factory())->create();

        $this->actingAs($admin)->post(
            route('participants.payments.store', $participant),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
            ],
        )->assertNotFound();

        $this->assertSame(0, Payment::query()->count());
    }

    #[Test]
    public function a_participant_cannot_record_a_payment(): void
    {
        $participant = Participant::factory()->create();

        $this->actingAs(User::factory()->create())->post(
            route('participants.payments.store', $participant),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
            ],
        )->assertForbidden();

        $this->assertSame(0, Payment::query()->count());
    }

    #[Test]
    public function a_guest_is_redirected_to_login_when_recording_a_payment(): void
    {
        $participant = Participant::factory()->create();

        $this->post(
            route('participants.payments.store', $participant),
            [
                'amount' => '1500',
                'method' => 'cash',
                'paid_at' => '2026-11-20',
            ],
        )->assertRedirect(route('login'));

        $this->assertSame(0, Payment::query()->count());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function supportedSlipFormats(): array
    {
        return [
            'pdf' => ['slip.pdf', 'application/pdf'],
            'jpg' => ['slip.jpg', 'image/jpeg'],
            'jpeg' => ['slip.jpeg', 'image/jpeg'],
            'png' => ['slip.png', 'image/png'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function heicSlipNames(): array
    {
        return [
            'heic' => ['slip.heic'],
            'heif' => ['slip.heif'],
        ];
    }

    private function heicUpload(string $name): UploadedFile
    {
        $fixture = __DIR__.'/Fixtures/sample.heic';

        if (! $this->canConvertHeic($fixture)) {
            $this->markTestSkipped('HEIC conversion requires Imagick with HEIC support or the ImageMagick CLI.');
        }

        $path = tempnam(sys_get_temp_dir(), 'heic-slip-');
        copy($fixture, $path);
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, 'image/heic', null, true);
    }

    private function canConvertHeic(string $source): bool
    {
        if (extension_loaded('imagick')) {
            try {
                $image = new Imagick($source);
                $image->setImageFormat('jpeg');
                $image->getImageBlob();
                $image->clear();

                return true;
            } catch (Throwable) {
                // Fall through to the ImageMagick command check.
            }
        }

        $target = tempnam(sys_get_temp_dir(), 'heic-check-').'.jpg';

        try {
            $process = Process::run(['magick', $source, '-quality', '90', $target]);

            return $process->successful() && is_file($target) && filesize($target) > 0;
        } catch (Throwable) {
            return false;
        } finally {
            @unlink($target);
        }
    }
}
