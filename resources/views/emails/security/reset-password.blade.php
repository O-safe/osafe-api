@extends('emails.layouts.o-safe', [
    'title' => 'Reset Your Password - O SAFE Security',
    'categorySubtitle' => 'Account Security'
])

@section('content')
<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    Reset Your Password
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Dear <strong>{{ $title }}. {{ $fullName }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
    We received a request to reset the password for your O SAFE Security account. To keep your home protection and family safety preferences secure, please click the button below to create a new password.
</p>

@include('emails.components.button', [
    'url' => $url,
    'text' => 'Set New Password',
    'style' => 'primary'
])

<p style="text-align: center; font-size: 12px; color: #dc2626; font-weight: 600; margin-bottom: 24px;">
    ⏱ This security link is valid for 10 minutes
</p>

@include('emails.components.security-notice', [
    'notice' => 'If you did not request this change, your account remains completely safe. You may safely disregard this email.'
])
@endsection
