@extends('emails.layouts.o-safe', [
    'title' => 'Welcome to O SAFE Security',
    'categorySubtitle' => 'Staff Onboarding'
])

@section('content')
<div style="margin-bottom: 12px;">
    @include('emails.components.status-badge', ['status' => 'verified', 'label' => 'Staff Account Provisioned'])
</div>

<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    Account Prepared & Activated
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Hello <strong>{{ $title }}. {{ $fullName }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
    Your administrative credentials for the <strong>O SAFE Security Management Portal</strong> have been successfully generated. You now have authorized staff access to oversee security operations, devices, and system telemetry.
</p>

@include('emails.components.otp-code', [
    'code' => $password,
    'label' => 'Temporary Admin Password',
    'expiry' => null
])

@include('emails.components.button', [
    'url' => config('app.frontend_url', config('app.url')) . '/admin/login',
    'text' => 'Log In to Admin Dashboard',
    'style' => 'primary'
])

@include('emails.components.alert', [
    'type' => 'warning',
    'title' => 'Mandatory Security Action',
    'message' => 'For data protection standards, you must set a new personal password immediately upon your first successful login.'
])

@include('emails.components.security-notice', [
    'notice' => 'Welcome to the O SAFE Security team! For technical support, contact the Systems Directorate.'
])
@endsection