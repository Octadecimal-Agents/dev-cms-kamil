<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowa rezerwacja</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #1a1a1a; padding: 24px 32px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: bold;">
                                2Wheels Rental
                            </h1>
                            <p style="color: #cccccc; margin: 8px 0 0; font-size: 14px;">
                                Nowa rezerwacja
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px;">
                            <h2 style="color: #333333; margin: 0 0 20px; font-size: 18px;">
                                Dane klienta
                            </h2>

                            <table width="100%" cellpadding="8" cellspacing="0" style="margin-bottom: 24px;">
                                <tr style="border-bottom: 1px solid #eeeeee;">
                                    <td style="color: #666666; font-size: 14px; width: 140px; vertical-align: top;"><strong>Imię i nazwisko:</strong></td>
                                    <td style="color: #333333; font-size: 14px;">{{ $reservation->customer_name }}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eeeeee;">
                                    <td style="color: #666666; font-size: 14px; vertical-align: top;"><strong>Email:</strong></td>
                                    <td style="color: #333333; font-size: 14px;">
                                        <a href="mailto:{{ $reservation->customer_email }}" style="color: #2563eb;">{{ $reservation->customer_email }}</a>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eeeeee;">
                                    <td style="color: #666666; font-size: 14px; vertical-align: top;"><strong>Telefon:</strong></td>
                                    <td style="color: #333333; font-size: 14px;">
                                        <a href="tel:{{ $reservation->customer_phone }}" style="color: #2563eb;">{{ $reservation->customer_phone }}</a>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="color: #333333; margin: 0 0 20px; font-size: 18px;">
                                Szczegóły rezerwacji
                            </h2>

                            <table width="100%" cellpadding="8" cellspacing="0" style="margin-bottom: 24px;">
                                @if($motorcycleName)
                                <tr style="border-bottom: 1px solid #eeeeee;">
                                    <td style="color: #666666; font-size: 14px; width: 140px; vertical-align: top;"><strong>Motocykl:</strong></td>
                                    <td style="color: #333333; font-size: 14px;">{{ $motorcycleName }}</td>
                                </tr>
                                @endif
                                <tr style="border-bottom: 1px solid #eeeeee;">
                                    <td style="color: #666666; font-size: 14px; vertical-align: top;"><strong>Data odbioru:</strong></td>
                                    <td style="color: #333333; font-size: 14px;">{{ $reservation->pickup_date?->format('d.m.Y') }}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eeeeee;">
                                    <td style="color: #666666; font-size: 14px; vertical-align: top;"><strong>Data zwrotu:</strong></td>
                                    <td style="color: #333333; font-size: 14px;">{{ $reservation->return_date?->format('d.m.Y') }}</td>
                                </tr>
                                @if($reservation->notes)
                                <tr style="border-bottom: 1px solid #eeeeee;">
                                    <td style="color: #666666; font-size: 14px; vertical-align: top;"><strong>Uwagi:</strong></td>
                                    <td style="color: #333333; font-size: 14px;">{{ $reservation->notes }}</td>
                                </tr>
                                @endif
                            </table>

                            <p style="color: #666666; font-size: 13px; margin: 24px 0 0; padding-top: 16px; border-top: 1px solid #eeeeee;">
                                Rezerwacja ID: {{ $reservation->id }}<br>
                                Data zgłoszenia: {{ $reservation->created_at?->format('d.m.Y H:i') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f8f8f8; padding: 16px 32px; text-align: center;">
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                Wiadomość wygenerowana automatycznie przez system 2Wheels Rental.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
