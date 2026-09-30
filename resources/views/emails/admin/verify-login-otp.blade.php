@extends('emails.layouts.o-safe', [
    'title' => 'Verify Admin Login - O SAFE Security',
    'categorySubtitle' => '2FA Authentication'
])

@section('content')
<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    Verify Administrative Access
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Hello <strong>{{ $title }}. {{ $fullName }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
    To complete your administrative sign-in to the O SAFE Security Portal, please enter the following One-Time Security Password (OTP) on the verification prompt:
</p>

@include('emails.components.otp-code', [
    'code' => $otp,
    'label' => 'Admin Passcode',
    'expiry' => 'Expires in 10 minutes'
])

@include('emails.components.card', [
    'title' => 'Authentication Request Information',
    'items' => [
        'Device' => $device,
        'Location' => $location
    ]
])

<p style="font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 20px;">
    If you did not attempt to log in to the administrative portal, please ignore this email or report the incident to O SAFE Security Support.
</p>

@include('emails.components.security-notice', [
    'notice' => 'O SAFE administrators will never request your OTP via email, phone, or third-party messaging.'
])
@endsection