@extends('emails.layouts.o-safe', [
    'title' => 'Support Ticket Update - O SAFE Security',
    'categorySubtitle' => 'Customer Support'
])

@section('content')
<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    Support Ticket Response Received
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Hello <strong>{{ $fullName ?? 'O SAFE Customer' }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
    An update has been posted to your support inquiry:
</p>

@include('emails.components.card', [
    'title' => 'Ticket Reference',
    'items' => [
        'Ticket ID' => $ticketId ?? 'TCK-0000',
        'Subject' => $subject ?? 'Security Query',
        'Status' => $status ?? 'In Progress'
    ]
])

<div style="background-color: #f8fafc; border-left: 4px solid #00D639; border-radius: 6px; padding: 16px 20px; margin: 20px 0; font-size: 14px; color: #334155; line-height: 1.6;">
    <strong>Latest Support Note:</strong><br />
    <em>{{ $messageContent ?? 'Our security engineering team has reviewed your query and updated your ticket status.' }}</em>
</div>

@if(isset($ticketUrl))
@include('emails.components.button', [
    'url' => $ticketUrl,
    'text' => 'View Full Ticket & Reply',
    'style' => 'primary'
])
@endif

@include('emails.components.security-notice', [
    'notice' => 'O SAFE Customer Support is active 24/7 to safeguard your installation and service.'
])
@endsection
