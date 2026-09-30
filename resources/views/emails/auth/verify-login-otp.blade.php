@extends('emails.layouts.o-safe', [
    'title' => 'Verify Your Login - O SAFE Security',
    'categorySubtitle' => 'Authentication'
])

@section('content')
<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    Verify Your Account Passcode
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Hello <strong>{{ $title }}. {{ $fullName }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
    To secure your sign-in to the O SAFE Security platform, please enter the following verification code:
</p>

@include('emails.components.otp-code', [
    'code' => $otp,
    'label' => 'Verification Code',
    'expiry' => 'Valid for 10 minutes'
])

@include('emails.components.card', [
    'title' => 'Login Details',
    'items' => [
        'Device / Platform' => $device,
        'Location' => $location
    ]
])

<p style="font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 20px;">
    If you did not attempt to sign in, please secure your account immediately or notify O SAFE Support.
</p>

@include('emails.components.security-notice', [
    'notice' => 'For your safety, never share your verification code with anyone.'
])
@endsection
