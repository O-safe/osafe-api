@extends('emails.layouts.o-safe', [
    'title' => 'Confirm Password Change - O SAFE Security',
    'categorySubtitle' => 'Security Authentication'
])

@section('content')
<div style="margin-bottom: 12px;">
    @include('emails.components.status-badge', ['status' => 'pending', 'label' => 'Security Action Requested'])
</div>

<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    Confirm Password Change
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Hello <strong>{{ $title }}. {{ $fullName }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
    A request has been initiated to modify the password for your O SAFE administrative access. Please confirm this action by clicking the authorization link below:
</p>

@include('emails.components.button', [
    'url' => $url,
    'text' => 'Confirm & Set New Password',
    'style' => 'primary'
])

<p style="text-align: center; font-size: 12px; color: #dc2626; font-weight: 600; margin-bottom: 24px;">
    ⏱ Security verification link expires in 10 minutes
</p>

@include('emails.components.security-notice', [
    'notice' => 'If you did not authorize this password modification, please alert the O SAFE Security Team immediately to protect your credentials.'
])
@endsection