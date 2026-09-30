@extends('emails.layouts.o-safe', [
    'title' => 'Reset Admin Password - O SAFE Security',
    'categorySubtitle' => 'Account Recovery'
])

@section('content')
<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    Password Reset Request
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Hello <strong>{{ $title }}. {{ $fullName }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
    We received a request to reset the password for your O SAFE administrative account. Click the button below to authorize this request and set your new credentials.
</p>

@include('emails.components.button', [
    'url' => $url,
    'text' => 'Reset Admin Password',
    'style' => 'primary'
])

<p style="text-align: center; font-size: 12px; color: #dc2626; font-weight: 600; margin-bottom: 24px;">
    ⏱ This password reset link will expire in 10 minutes
</p>

@include('emails.components.security-notice', [
    'notice' => 'If you did not request a password reset, no further action is required. Your administrative credentials remain secure.'
])
@endsection