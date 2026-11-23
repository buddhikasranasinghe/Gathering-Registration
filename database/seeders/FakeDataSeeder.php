<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

final class FakeDataSeeder extends Seeder
{
    public function run(): void
    {
        // Clean up orphaned role assignments left by previously deleted users.
        DB::statement("DELETE FROM model_has_roles WHERE model_type = 'App\\Identity\\Entities\\User' AND model_id NOT IN (SELECT id FROM users)");

        if (Participant::query()->count() >= 50) {
            $this->command?->info('Fake data already present; skipping.');

            return;
        }

        $batches = Batch::query()->whereIn('name', ['36', '37', '38', '39', '40'])->get();

        if ($batches->isEmpty()) {
            $this->call(BatchSeeder::class);
            $batches = Batch::query()->whereIn('name', ['36', '37', '38', '39', '40'])->get();
        }

        $superAdminId = User::query()
            ->where('email', 'buddhikasranasinghe96@gmail.com')
            ->value('id');

        $emails = [];
        $phones = [];
        $nics = [];

        $participantRows = [];

        for ($i = 0; $i < 150; $i++) {
            $email = $this->uniqueEmail($emails);
            $phone = $this->uniquePhone($phones);
            $nic = $this->uniqueNic($nics);
            $batch = $batches->random();
            $archived = fake()->boolean(10);

            $participantRows[] = [
                'name' => fake()->name(),
                'email' => $email,
                'call_name' => fake()->firstName(),
                'batch_id' => $batch->id,
                'phone' => $phone,
                'nic' => $nic,
                'spouse_attending' => fake()->boolean(30),
                'spouse_name' => fake()->boolean(30) ? fake()->name() : null,
                'import_key' => 'nic:'.$nic,
                'last_imported_at' => now(),
                'archived_at' => $archived ? now() : null,
                'archive_note' => $archived ? 'Duplicate submission.' : null,
                'archived_by' => $archived ? $superAdminId : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        for ($i = 0; $i < 100; $i++) {
            $email = $this->uniqueEmail($emails);
            $batch = $batches->random();

            $participantRows[] = [
                'name' => fake()->name(),
                'email' => $email,
                'call_name' => null,
                'batch_id' => $batch->id,
                'phone' => null,
                'nic' => null,
                'spouse_attending' => null,
                'spouse_name' => null,
                'import_key' => null,
                'last_imported_at' => null,
                'archived_at' => null,
                'archive_note' => null,
                'archived_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($participantRows, 100) as $chunk) {
            Participant::query()->insert($chunk);
        }

        $userRows = [];
        $importedParticipants = collect($participantRows);

        foreach ($importedParticipants as $participant) {
            $userRows[] = [
                'name' => $participant['name'],
                'email' => $participant['email'],
                'password' => null,
                'role' => UserRole::User->value,
                'batch_id' => $participant['batch_id'],
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($batches as $batch) {
            $email = $this->uniqueEmail($emails);

            $userRows[] = [
                'name' => fake()->name(),
                'email' => $email,
                'password' => null,
                'role' => UserRole::Admin->value,
                'batch_id' => $batch->id,
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($userRows, 100) as $chunk) {
            User::query()->insert($chunk);
        }

        foreach ($userRows as $row) {
            if ($row['role'] === UserRole::Admin->value) {
                $role = Role::findOrCreate(
                    UserRole::Admin->value,
                    config('auth.defaults.guard'),
                );

                User::query()->where('email', $row['email'])->first()?->syncRoles([$role]);
            }
        }

        $userRole = Role::findOrCreate(
            UserRole::User->value,
            config('auth.defaults.guard'),
        );

        User::query()
            ->where('role', UserRole::User->value)
            ->get()
            ->each(fn (User $user) => $user->syncRoles([$userRole]));
    }

    private function uniqueEmail(array &$emails): string
    {
        do {
            $email = fake()->unique()->safeEmail();
        } while (in_array($email, $emails, true));

        $emails[] = $email;

        return $email;
    }

    private function uniquePhone(array &$phones): string
    {
        do {
            $phone = '07'.str_pad((string) fake()->numberBetween(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (in_array($phone, $phones, true));

        $phones[] = $phone;

        return $phone;
    }

    private function uniqueNic(array &$nics): string
    {
        do {
            $nic = fake()->numerify('#########').'V';
        } while (in_array($nic, $nics, true));

        $nics[] = $nic;

        return $nic;
    }
}
