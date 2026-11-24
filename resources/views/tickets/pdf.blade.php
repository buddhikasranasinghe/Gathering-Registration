<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; size: 720pt 233pt; }

        @font-face {
            font-family: 'Edo';
            src: url("file://{{ resource_path('fonts/Edo.ttf') }}") format("truetype");
            font-weight: normal;
            font-style: normal;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            width: 720pt;
            height: 233pt;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
        }

        .ticket {
            position: relative;
            width: 720pt;
            height: 233pt;
            overflow: hidden;
        }

        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 720pt;
            height: 233pt;
        }

        .price {
            position: absolute;
            left: 520pt;
            top: 176pt;
            width: 180pt;
            color: #111827;
            font-family: 'Edo', 'DejaVu Sans', sans-serif;
            font-size: 20pt;
            text-align: center;
            white-space: nowrap;
        }

        .attendees {
            position: absolute;
            left: 200pt;
            top: 208pt;
            width: 260pt;
            color: #fff7ed;
            font-size: 8pt;
            text-align: center;
            line-height: 1.25;
        }

        .qr {
            position: absolute;
            left: 553pt;
            top: 59pt;
            width: 115pt;
            height: 115pt;
            background: #ffffff;
        }

        .ticket-number {
            position: absolute;
            left: 515pt;
            top: 16pt;
            width: 190pt;
            color: #111827;
            font-family: 'Edo', 'DejaVu Sans', sans-serif;
            font-size: 20pt;
            text-align: center;
            white-space: nowrap;
        }

        .issued {
            position: absolute;
            left: 545pt;
            top: 216pt;
            width: 132pt;
            color: #374151;
            font-size: 6.5pt;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <img class="background" src="{{ $templateImage }}" alt="">

        <div class="ticket-number">{{ $ticketNumber }}</div>

        <img class="qr" src="{{ $qrImage }}" alt="Ticket verification QR code">
        <div class="price">Rs. {{ $price }}</div>
        <div class="issued">Issued {{ $issuedAt }}</div>

        <div class="attendees">Admits: {{ implode(' • ', $attendees) }}</div>
    </div>
</body>
</html>
