<?php

declare(strict_types=1);

namespace App\Participation\Services;

use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantProgressStatus;
use App\Participation\Entities\Payment;
use App\Participation\Entities\TicketStatus;
use Illuminate\Support\Collection;

final class TicketEligibilityService
{
    public function __construct(private TicketPriceCalculator $prices) {}

    /**
     * @return array{
     *     ticketPrice: float,
     *     totalPaid: float,
     *     remaining: float,
     *     refund: float,
     *     attendees: array<int, array{name: string, relation: ?string}>,
     *     canGenerate: bool,
     *     message: ?string,
     *     hasActiveTicket: bool
     * }
     */
    public function assess(Participant $participant): array
    {
        $breakdown = $this->prices->calculate($participant);
        $payments = $participant->relationLoaded('payments')
            ? $participant->payments
            : $participant->payments()->get();
        $totalPaid = $this->totalPaid($participant, $payments);
        $remaining = max(0.0, $breakdown['total'] - $totalPaid);
        $refund = max(0.0, $totalPaid - $breakdown['total']);
        $attendees = $this->attendees($participant);
        $hasActiveTicket = $participant->relationLoaded('issuedTickets')
            ? $participant->issuedTickets->contains(fn ($ticket): bool => $ticket->isActive())
            : $participant->issuedTickets()
                ->where('status', TicketStatus::Active)
                ->exists();

        $message = match (true) {
            $attendees === [] => 'This participant is not attending, so a ticket cannot be generated.',
            $remaining > 0.0 => 'Payment is not complete. Outstanding balance: '.number_format($remaining, 2).'.',
            $hasActiveTicket => 'An active ticket already exists for this participant.',
            default => null,
        };

        return [
            'ticketPrice' => $breakdown['total'],
            'totalPaid' => $totalPaid,
            'remaining' => $remaining,
            'refund' => $refund,
            'attendees' => $attendees,
            'canGenerate' => $message === null,
            'message' => $message,
            'hasActiveTicket' => $hasActiveTicket,
        ];
    }

    public function progressStatus(Participant $participant): ParticipantProgressStatus
    {
        $assessment = $this->assess($participant);

        return match (true) {
            $assessment['hasActiveTicket'] => ParticipantProgressStatus::TicketIssued,
            $assessment['remaining'] <= 0.0
                && ($assessment['ticketPrice'] > 0.0 || $assessment['totalPaid'] > 0.0) => ParticipantProgressStatus::PaymentComplete,
            $assessment['totalPaid'] > 0.0 => ParticipantProgressStatus::PaymentIncomplete,
            default => ParticipantProgressStatus::NotPaid,
        };
    }

    /**
     * @param  Collection<int, Payment>  $payments
     */
    private function totalPaid(Participant $participant, Collection $payments): float
    {
        $total = (float) $payments->sum('amount');

        $relations = $participant->relationLoaded('relatedParticipants')
            ? $participant->relatedParticipants
            : $participant->relatedParticipants()->get();

        foreach ($relations as $relation) {
            if ($relation->archived_at !== null) {
                continue;
            }

            $relationPayments = $relation->relationLoaded('payments')
                ? $relation->payments
                : $relation->payments()->get();

            $total += (float) $relationPayments->sum('amount');
        }

        return $total;
    }

    /**
     * @return array<int, array{name: string, relation: ?string}>
     */
    private function attendees(Participant $participant): array
    {
        $attendees = [];

        if ($participant->attending) {
            $attendees[] = ['name' => $participant->name, 'relation' => null];
        }

        $relations = $participant->relationLoaded('relatedParticipants')
            ? $participant->relatedParticipants
            : $participant->relatedParticipants()->get();

        foreach ($relations as $relation) {
            if ($relation->archived_at !== null || ! $relation->attending) {
                continue;
            }

            $attendees[] = [
                'name' => $relation->name,
                'relation' => $relation->relation?->label(),
            ];
        }

        return $attendees;
    }
}
