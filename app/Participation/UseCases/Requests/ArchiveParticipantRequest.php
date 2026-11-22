<?php

declare(strict_types=1);

namespace App\Participation\UseCases\Requests;

use App\Participation\Entities\Participant;

final readonly class ArchiveParticipantRequest
{
    public string $note;

    public function __construct(
        public Participant $participant,
        public int $archivedBy,
        string $note,
    ) {
        $this->note = trim($note);
    }
}
