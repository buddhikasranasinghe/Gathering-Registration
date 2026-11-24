<x-mail::message>
# Your gathering ticket

Hello {{ $attendeeName }},

Your ticket is attached to this email.

<x-mail::panel>
Ticket number: **{{ $ticketNumber }}**
</x-mail::panel>

Please keep this email and show the ticket at the entrance.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
