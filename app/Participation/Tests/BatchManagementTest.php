<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Batch;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class BatchManagementTest extends TestCase
{
    #[Test]
    public function when_a_super_admin_views_batches_then_the_batch_list_is_shown(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $batch = Batch::factory()->create(['name' => 'Batch Thirty Seven']);

        $response = $this->actingAs($superAdmin)->get(route('batches.index'));

        $this->assertBatchListIsShown($response, $batch);
    }

    #[Test]
    public function when_a_super_admin_creates_a_batch_then_the_normalized_mapper_is_saved(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->post(route('batches.store'), [
            'name' => 'Batch Thirty Seven',
            'excel_mapper_name' => '  EXCEL Batch 37  ',
        ]);

        $this->assertBatchWasCreated($response);
    }

    #[Test]
    public function when_a_super_admin_updates_a_batch_then_the_new_values_are_saved(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $batch = Batch::factory()->create();

        $response = $this->actingAs($superAdmin)->patch(route('batches.update', $batch), [
            'name' => 'Batch Thirty Eight',
            'excel_mapper_name' => 'EXCEL Batch 38',
        ]);

        $this->assertBatchWasUpdated($response, $batch);
    }

    #[DataProvider('duplicateBatchValues')]
    #[Test]
    public function when_a_super_admin_uses_a_duplicate_batch_value_then_it_is_rejected(string $field): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $batch = Batch::factory()->create([
            'name' => 'Batch Thirty Seven',
            'excel_mapper_name' => 'excel batch 37',
        ]);

        $response = $this->actingAs($superAdmin)->post(route('batches.store'), [
            'name' => $field === 'name' ? $batch->name : 'Another batch',
            'excel_mapper_name' => $field === 'excel_mapper_name' ? 'EXCEL BATCH 37' : 'another mapper',
        ]);

        $this->assertDuplicateValueWasRejected($response, $field);
    }

    #[DataProvider('nonSuperAdminRoles')]
    #[Test]
    public function when_a_non_super_admin_manages_batches_then_the_request_is_forbidden(UserRole $role): void
    {
        $user = match ($role) {
            UserRole::Admin => User::factory()->admin()->create(),
            UserRole::MainTreasurer => User::factory()->mainTreasurer()->create(),
            default => User::factory()->create(),
        };

        $response = $this->actingAs($user)->get(route('batches.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function when_a_guest_views_batches_then_the_request_redirects_to_login(): void
    {
        $response = $this->get(route('batches.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function duplicateBatchValues(): array
    {
        return [
            'name' => ['name'],
            'excel mapper name' => ['excel_mapper_name'],
        ];
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function nonSuperAdminRoles(): array
    {
        return [
            'admin' => [UserRole::Admin],
            'main_treasurer' => [UserRole::MainTreasurer],
            'user' => [UserRole::User],
        ];
    }

    private function assertBatchListIsShown(TestResponse $response, Batch $batch): void
    {
        $response->assertOk();
        $response->assertSee($batch->name);
    }

    private function assertBatchWasCreated(TestResponse $response): void
    {
        $response->assertRedirect(route('batches.index'));
        $this->assertDatabaseHas('batches', [
            'name' => 'Batch Thirty Seven',
            'excel_mapper_name' => 'excel batch 37',
        ]);
    }

    private function assertBatchWasUpdated(TestResponse $response, Batch $batch): void
    {
        $response->assertRedirect(route('batches.index'));
        $batch->refresh();
        $this->assertSame('Batch Thirty Eight', $batch->name);
        $this->assertSame('excel batch 38', $batch->excel_mapper_name);
    }

    private function assertDuplicateValueWasRejected(TestResponse $response, string $field): void
    {
        $response->assertSessionHasErrors($field);
        $this->assertSame(1, Batch::query()->count());
    }
}
