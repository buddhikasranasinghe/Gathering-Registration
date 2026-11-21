<x-mail::message>
# Your sign-in code

Use the code below to finish signing in. It expires in {{ $expiresInMinutes }} minutes.

<x-mail::panel>
<span style="font-size: 28px; font-weight: 700; letter-spacing: 8px;">{{ $code }}</span>
</x-mail::panel>

If you did not request this code, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
