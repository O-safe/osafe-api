@props(['type' => 'info', 'title' => null, 'message' => null])

@php
    $theme = match($type) {
        'danger' => [
            'bg' => '#fef2f2',
            'border' => '#fca5a5',
            'icon' => '🚨',
            'title_color' => '#991b1b',
            'text_color' => '#991b1b',
        ],
        'warning' => [
            'bg' => '#fffbeb',
            'border' => '#fde68a',
            'icon' => '⚠️',
            'title_color' => '#92400e',
            'text_color' => '#92400e',
        ],
        'success' => [
            'bg' => '#f0fdf4',
            'border' => '#86efac',
            'icon' => '✅',
            'title_color' => '#166534',
            'text_color' => '#166534',
        ],
        default => [
            'bg' => '#f0f9ff',
            'border' => '#bae6fd',
            'icon' => 'ℹ️',
            'title_color' => '#075985',
            'text_color' => '#075985',
        ],
    };
@endphp

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0;">
    <tr>
        <td style="background-color: {{ $theme['bg'] }}; border-left: 4px solid {{ $theme['border'] }}; border-radius: 8px; padding: 16px 20px;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    @if($title)
                    <td style="font-size: 14px; font-weight: 700; color: {{ $theme['title_color'] }}; padding-bottom: 4px;">
                        {{ $theme['icon'] }} {{ $title }}
                    </td>
                    @endif
                </tr>
                <tr>
                    <td style="font-size: 13px; color: {{ $theme['text_color'] }}; line-height: 1.5;">
                        {{ $message ?? $slot }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
