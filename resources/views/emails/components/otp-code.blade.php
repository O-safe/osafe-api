@props(['code', 'label' => 'One-Time Security Code', 'expiry' => 'Expires in 10 minutes'])

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 28px 0;">
    <tr>
        <td align="center">
            <table border="0" cellpadding="0" cellspacing="0" class="otp-box" style="background-color: #f0fdf4; border: 2px dashed #86efac; border-radius: 14px; padding: 24px 32px; min-width: 320px; text-align: center;">
                <tr>
                    <td align="center" style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 2px; padding-bottom: 8px;">
                        {{ $label }}
                    </td>
                </tr>
                <tr>
                    <td align="center" class="otp-code" style="font-size: 36px; font-weight: 800; font-family: 'Consolas', 'Courier New', monospace; letter-spacing: 8px; color: #008F3D; padding: 6px 0;">
                        {{ $code }}
                    </td>
                </tr>
                @if($expiry)
                <tr>
                    <td align="center" style="font-size: 12px; font-weight: 600; color: #dc2626; padding-top: 8px;">
                        ⏱ {{ $expiry }}
                    </td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
