<?php

declare(strict_types=1);

namespace App\Participation\Services;

use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantRelation;

final class TicketPriceCalculator
{
    /**
     * @return array{total: float, lines: array<int, array{label: string, quantity: int, unitPrice: float, amount: float}>}
     */
    public function calculate(Participant $participant): array
    {
        $pricePerPerson = (float) config('tickets.price_per_person');

        $categories = [
            'Participant' => ['unitPrice' => $pricePerPerson, 'quantity' => (int) $participant->attending],
            'Spouse' => ['unitPrice' => $pricePerPerson, 'quantity' => 0],
            'Child' => ['unitPrice' => (float) config('tickets.price_per_child'), 'quantity' => 0],
        ];

        $relatedParticipants = $participant->relationLoaded('relatedParticipants')
            ? $participant->relatedParticipants
            : $participant->relatedParticipants()->get();

        foreach ($relatedParticipants as $relation) {
            if ($relation->archived_at !== null || ! $relation->attending) {
                continue;
            }

            $category = $relation->relation === ParticipantRelation::Child ? 'Child' : 'Spouse';
            $categories[$category]['quantity']++;
        }

        $lines = [];
        $total = 0.0;

        foreach ($categories as $label => $category) {
            if ($category['quantity'] === 0) {
                continue;
            }

            $amount = $category['unitPrice'] * $category['quantity'];
            $total += $amount;
            $lines[] = [
                'label' => $label,
                'quantity' => $category['quantity'],
                'unitPrice' => $category['unitPrice'],
                'amount' => $amount,
            ];
        }

        return ['total' => $total, 'lines' => $lines];
    }
}
