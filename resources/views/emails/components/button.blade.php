@props(['url', 'text', 'align' => 'center', 'style' => 'primary'])

@php
    $bgStyle = match($style) {
        'danger' => 'background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: 1px solid #b91c1c; color: #ffffff;',
        'secondary' => 'background-color: #0f172a; border: 1px solid #1e293b; color: #ffffff;',
        'outline' => 'background-color: #ffffff; border: 2px solid #00D639; color: #008F3D;',
        default => 'background: linear-gradient(135deg, #00D639 0%, #008F3D 100%); border: 1px solid #00B32F; color: #ffffff;'
    };
    $textColor = ($style === 'outline') ? '#008F3D' : '#ffffff';
@endphp

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 28px 0;">
    <tr>
        <td align="{{ $align }}">
            <table border="0" cellpadding="0" cellspacing="0" class="button-cta">
                <tr>
                    <td align="center" style="border-radius: 10px; {{ $bgStyle }} box-shadow: 0 4px 14px rgba(0, 214, 57, 0.25);">
                        <a href="{{ $url }}" target="_blank" style="font-size: 15px; font-weight: 700; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: {{ $textColor }} !important; text-decoration: none; padding: 15px 36px; display: inline-block; border-radius: 10px; letter-spacing: 0.3px;">
                            {{ $text }}
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
