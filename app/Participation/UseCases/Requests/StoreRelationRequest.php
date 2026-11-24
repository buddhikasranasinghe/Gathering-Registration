<?php

declare(strict_types=1);

namespace App\Participation\UseCases\Requests;

use App\Identity\Entities\User;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantRelation;

final readonly class StoreRelationRequest
{
    public function __construct(
        public Participant $participant,
        public User $changedBy,
        public string $name,
        public ParticipantRelation $relation,
    ) {}
}
