@props(['title' => null, 'items' => []])

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
    @if($title)
    <tr>
        <td style="background-color: #f1f5f9; padding: 10px 18px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 1px;">
            {{ $title }}
        </td>
    </tr>
    @endif
    <tr>
        <td style="padding: 16px 20px;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                @foreach($items as $label => $value)
                <tr>
                    <td valign="top" style="padding: 6px 0; font-size: 13px; color: #64748b; font-weight: 600; width: 110px;">
                        {{ $label }}:
                    </td>
                    <td valign="top" style="padding: 6px 0; font-size: 13px; color: #0f172a; font-weight: 700;">
                        {{ $value }}
                    </td>
                </tr>
                @endforeach
                {{ $slot ?? '' }}
            </table>
        </td>
    </tr>
</table>
