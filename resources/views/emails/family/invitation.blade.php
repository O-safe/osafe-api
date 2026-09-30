@extends('emails.layouts.o-safe', [
    'title' => 'Family Safety Circle Invitation - O SAFE Security',
    'categorySubtitle' => 'Family Protection'
])

@section('content')
<div style="margin-bottom: 12px;">
    @include('emails.components.status-badge', ['status' => 'pending', 'label' => 'Invitation Pending'])
</div>

<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    Join a Family Safety Circle
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Hello <strong>{{ $inviteeName ?? 'Safety Member' }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
    <strong>{{ $inviterName ?? 'A family member' }}</strong> has invited you to join their <strong>{{ $familyName ?? 'O SAFE Family Group' }}</strong> safety circle.
</p>

@include('emails.components.card', [
    'title' => 'Family Group Overview',
    'items' => [
        'Family Name' => $familyName ?? 'Family Circle',
        'Invited By' => $inviterName ?? 'Group Admin',
        'Access Level' => $roleName ?? 'Member'
    ]
])

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
    Joining this family circle allows shared emergency alerts, live safe zone status, and physical device coordination.
</p>

@if(isset($acceptUrl))
@include('emails.components.button', [
    'url' => $acceptUrl,
    'text' => 'Accept Invitation & Join Circle',
    'style' => 'primary'
])
@endif

@include('emails.components.security-notice', [
    'notice' => 'You will be able to review and manage your privacy controls at any time after accepting.'
])
@endsection
