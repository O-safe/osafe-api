@extends('emails.layouts.o-safe', [
    'title' => 'Account Locked - O SAFE Security',
    'categorySubtitle' => 'Administrative Security',
    'securityBanner' => 'Administrative Account Protection Locked'
])

@section('content')
<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    Administrative Access Restricted
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Hello <strong>{{ $title }}. {{ $fullName }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
    To safeguard the O SAFE Security system and your administrative credentials, your account has been temporarily locked following multiple unsuccessful authentication attempts.
</p>

@include('emails.components.card', [
    'title' => 'Security Incident Details',
    'items' => [
        'Device' => $device,
        'Location' => $location,
        'Browser' => $browser
    ]
])

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
    To verify your identity and restore administrative access, please click the secure unlock button below.
</p>

@include('emails.components.button', [
    'url' => $url,
    'text' => 'Verify & Unlock Account',
    'style' => 'danger'
])

<p style="text-align: center; font-size: 12px; color: #dc2626; font-weight: 600; margin-bottom: 24px;">
    ⏱ Security unlock link is valid for 10 minutes
</p>

@include('emails.components.security-notice', [
    'notice' => 'If you did not attempt to sign in, please contact the O SAFE Systems Security Unit immediately.'
])
@endsection