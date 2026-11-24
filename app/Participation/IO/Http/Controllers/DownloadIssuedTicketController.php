<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\Services\TicketStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadIssuedTicketController extends Controller
{
    public function __invoke(
        Request $request,
        Participant $participant,
        IssuedTicket $ticket,
        TicketStorage $storage,
    ): StreamedResponse {
        $user = $request->user();

        if ($user->role === UserRole::Admin && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }

        if (
            $ticket->participant_id !== $participant->getKey()
            || ! $storage->exists($ticket->file_path, $ticket->file_disk)
        ) {
            abort(404);
        }

        return $storage->download(
            (string) $ticket->file_path,
            basename((string) $ticket->file_path),
            $ticket->file_disk,
        );
    }
}
