<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\ImportStatus;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantImportRun;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ParticipantImportTest extends TestCase
{
    #[Test]
    public function when_a_super_admin_uploads_a_csv_then_participants_are_imported_immediately(): void
    {
        $user = User::factory()->superAdmin()->create();

        $response = $this->actingAs($user)->post(
            route('participants.import'),
            ['file' => $this->csvUpload()],
        );

        $this->assertCsvWasImported($response, $user);
    }

    #[Test]
    public function when_a_super_admin_uploads_an_excel_file_then_participants_are_imported_immediately(): void
    {
        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.import'),
            ['file' => $this->xlsxUpload()],
        );

        $this->assertExcelWasImported($response);
    }

    #[Test]
    public function when_an_imported_row_matches_an_archived_participant_then_the_participant_is_not_changed(): void
    {
        $participant = Participant::factory()->imported()->archived()->create(['name' => 'Archived Name']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.import'),
            ['file' => $this->csvUpload()],
        );

        $this->assertArchivedParticipantWasNotChanged($response, $participant);
    }

    #[Test]
    public function when_an_imported_row_matches_a_self_registered_participant_then_a_new_imported_participant_is_created(): void
    {
        Participant::factory()->registered()->create(['phone' => '0773370555']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.import'),
            ['file' => $this->csvUpload()],
        );

        $this->assertSelfRegisteredParticipantWasNotChanged($response);
    }

    #[Test]
    public function when_the_uploaded_file_has_an_invalid_extension_then_it_is_rejected(): void
    {
        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.import'),
            ['file' => UploadedFile::fake()->create('participants.txt', 1, 'text/plain')],
        );

        $response->assertSessionHasErrors('file');
    }

    #[DataProvider('nonSuperAdminRoles')]
    #[Test]
    public function when_a_non_super_admin_uploads_a_file_then_it_is_forbidden(UserRole $role): void
    {
        $user = $role === UserRole::Admin
            ? User::factory()->admin()->create()
            : User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('participants.import'),
            ['file' => $this->csvUpload()],
        );

        $response->assertForbidden();
    }

    #[Test]
    public function when_a_guest_uploads_a_file_then_it_redirects_to_login(): void
    {
        $response = $this->post(route('participants.import'), ['file' => $this->csvUpload()]);

        $response->assertRedirect(route('login'));
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function nonSuperAdminRoles(): array
    {
        return [
            'admin' => [UserRole::Admin],
            'participant' => [UserRole::User],
        ];
    }

    private function csvUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('participants.csv', implode("\n", [
            'Timestamp,Name,Call name,Batch,Phone,Spouse attending,,,,NIC,Spouse name,',
            ',Chathuranga Ranasingh,Cos,"37 (2013 - 2014)",0773370555,Yes,,,,933040186V,R.M.T Sooryamali,',
        ]));
    }

    private function xlsxUpload(): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Timestamp', 'Name', 'Call name', 'Batch', 'Phone', 'Spouse attending', '', '', '', 'NIC', 'Spouse name'],
            ['', 'Ishara Weerasinghe', '', '', '0766035512', 'No', '', '', '', '', ''],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'participants-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile($path, 'participants.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function assertCsvWasImported($response, User $user): void
    {
        $response->assertRedirect();

        $run = ParticipantImportRun::query()->sole();
        $participant = Participant::query()->sole();

        $this->assertSame(ImportStatus::Completed, $run->status);
        $this->assertSame('participants.csv', $run->source_name);
        $this->assertSame($user->getKey(), $run->requested_by);
        $this->assertSame(1, $run->added_count);
        $this->assertSame('Chathuranga Ranasingh', $participant->name);
        $this->assertSame('nic:933040186V', $participant->import_key);
    }

    private function assertExcelWasImported($response): void
    {
        $response->assertRedirect();

        $this->assertSame(
            'Ishara Weerasinghe',
            Participant::query()->sole()->name,
        );
    }

    private function assertArchivedParticipantWasNotChanged($response, Participant $participant): void
    {
        $response->assertRedirect();
        $participant->refresh();

        $this->assertSame('Archived Name', $participant->name);
        $this->assertSame(1, ParticipantImportRun::query()->sole()->unchanged_count);
    }

    private function assertSelfRegisteredParticipantWasNotChanged($response): void
    {
        $response->assertRedirect();

        $this->assertSame(2, Participant::query()->count());
        $this->assertSame(1, Participant::query()->whereNotNull('import_key')->count());
    }
}
