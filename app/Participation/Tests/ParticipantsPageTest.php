<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Participation\Entities\Participant;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;

final class ParticipantsPageTest extends TestCase
{
    #[Test]
    public function when_a_super_admin_views_the_page_then_the_roster_and_import_form_are_shown(): void
    {
        $participant = Participant::factory()->imported()->create(['name' => 'Chathuranga Ranasingh']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index'));

        $this->assertRosterShownToSuperAdmin($response, $participant);
    }

    #[Test]
    public function when_an_admin_views_the_page_then_the_import_form_is_hidden(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('participants.index'));

        $this->assertImportIsHiddenFromAdmin($response);
    }

    #[Test]
    public function when_a_participant_views_the_page_then_it_is_forbidden(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('participants.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function when_a_guest_views_the_page_then_it_redirects_to_login(): void
    {
        $response = $this->get(route('participants.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function when_the_search_filter_is_applied_then_only_matching_participants_are_listed(): void
    {
        Participant::factory()->create(['name' => 'Alice Perera']);
        Participant::factory()->create(['name' => 'Bob Silva']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['search' => 'Alice']));

        $this->assertRosterIsFiltered($response);
    }

    #[Test]
    public function when_the_archived_filter_is_applied_then_only_archived_participants_are_listed(): void
    {
        Participant::factory()->create(['name' => 'Active Person']);
        Participant::factory()->archived()->create(['name' => 'Archived Person']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['archived' => '1']));

        $this->assertArchivedRosterIsShown($response);
    }

    private function assertRosterShownToSuperAdmin(TestResponse $response, Participant $participant): void
    {
        $response->assertOk();
        $response->assertSee($participant->name);
        $response->assertSee('Import spreadsheet');
        $response->assertViewHas('canImport', true);
    }

    private function assertImportIsHiddenFromAdmin(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertDontSee('Import spreadsheet');
        $response->assertViewHas('canImport', false);
    }

    private function assertRosterIsFiltered(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === 1
                && $participants->first()->name === 'Alice Perera',
        );
    }

    private function assertArchivedRosterIsShown(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === 1
                && $participants->first()->name === 'Archived Person'
                && $participants->first()->isArchived(),
        );
    }
}
