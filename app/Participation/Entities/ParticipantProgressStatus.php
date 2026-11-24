<?php

declare(strict_types=1);

namespace App\Participation\Entities;

enum ParticipantProgressStatus: string
{
    case NotPaid = 'not_paid';
    case PaymentIncomplete = 'payment_incomplete';
    case PaymentComplete = 'payment_complete';
    case TicketIssued = 'ticket_issued';

    public function label(): string
    {
        return match ($this) {
            self::NotPaid => 'Not Paid',
            self::PaymentIncomplete => 'Payment Incomplete',
            self::PaymentComplete => 'Payment Complete',
            self::TicketIssued => 'Ticket Issued',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::NotPaid => 'rose',
            self::PaymentIncomplete => 'amber',
            self::PaymentComplete => 'emerald',
            self::TicketIssued => 'violet',
        };
    }

    public function progressStep(): int
    {
        return match ($this) {
            self::NotPaid, self::PaymentIncomplete => 1,
            self::PaymentComplete => 2,
            self::TicketIssued => 3,
        };
    }

    /**
     * @return array<int, self>
     */
    public static function progressSteps(): array
    {
        return [self::NotPaid, self::PaymentComplete, self::TicketIssued];
    }
}
