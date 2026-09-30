@extends('emails.layouts.o-safe', [
    'title' => 'Security Alert: Device Event Detected - O SAFE Security',
    'categorySubtitle' => 'Device Telemetry',
    'securityBanner' => 'Security Event Alert'
])

@section('content')
<div style="margin-bottom: 12px;">
    @include('emails.components.status-badge', ['status' => 'danger', 'label' => $alertLevel ?? 'CRITICAL'])
</div>

<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    {{ $alertTitle ?? 'Physical Device Alert' }}
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Hello <strong>{{ $fullName ?? 'O SAFE User' }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
    An urgent event has been reported by your monitored physical security device:
</p>

@include('emails.components.card', [
    'title' => 'Device Telemetry Summary',
    'items' => [
        'Device Name' => $deviceName ?? 'O SAFE Hub',
        'Device Serial' => $serialNumber ?? 'N/A',
        'Event Type' => $eventType ?? 'Tamper Alert / Offline',
        'Location Zone' => $zoneName ?? 'Main Safe Zone',
        'Timestamp' => $timestamp ?? now()->toDayDateTimeString()
    ]
])

@if(isset($actionUrl))
@include('emails.components.button', [
    'url' => $actionUrl,
    'text' => 'Inspect Live Telemetry & Control Panel',
    'style' => 'danger'
])
@endif

@include('emails.components.security-notice', [
    'notice' => 'Realtime telemetry alerts are dispatched automatically via your O SAFE physical device cluster.'
])
@endsection
