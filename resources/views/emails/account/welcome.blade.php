@extends('emails.layouts.o-safe', [
    'title' => 'Welcome to O SAFE Security',
    'categorySubtitle' => 'Account Activation'
])

@section('content')
<div style="margin-bottom: 12px;">
    @include('emails.components.status-badge', ['status' => 'active', 'label' => 'Account Activated'])
</div>

<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    Welcome to Next-Gen Safety
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Hello <strong>{{ $title }}. {{ $fullName }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
    We are delighted to welcome you to <strong>O SAFE Security</strong>. Your account (<strong>{{ $email }}</strong>) is now fully active, granting you access to our physical safety platform, family security circles, and device telemetry.
</p>

@include('emails.components.button', [
    'url' => config('app.frontend_url', config('app.url')) . '/login',
    'text' => 'Log In to O SAFE Portal',
    'style' => 'primary'
])

@include('emails.components.alert', [
    'type' => 'info',
    'title' => 'Security Notice',
    'message' => 'For your security, please sign in through the official portal to set or update your personal account credentials.'
])

@include('emails.components.security-notice', [
    'notice' => 'Thank you for trusting O SAFE Security to safeguard what matters most.'
])
@endsection
