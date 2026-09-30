@extends('emails.layouts.o-safe', [
    'title' => 'Billing Receipt & Subscription Status - O SAFE Security',
    'categorySubtitle' => 'Billing & Subscription'
])

@section('content')
<div style="margin-bottom: 12px;">
    @include('emails.components.status-badge', ['status' => 'paid', 'label' => 'Payment Successful'])
</div>

<h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
    Subscription Receipt
</h2>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
    Hello <strong>{{ $fullName ?? 'Valued Customer' }}</strong>,
</p>

<p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
    Thank you for maintaining active coverage with O SAFE Security. Your billing transaction has processed successfully.
</p>

@include('emails.components.card', [
    'title' => 'Transaction Details',
    'items' => [
        'Subscription Plan' => $planName ?? 'O SAFE Premium Guard',
        'Billing Reference' => $reference ?? 'TXN-OSAFE-000',
        'Amount Paid' => $amountFormatted ?? '$0.00',
        'Payment Provider' => $provider ?? 'Paystack',
        'Next Billing Date' => $nextBillingDate ?? now()->addMonth()->format('F j, Y')
    ]
])

@if(isset($actionUrl))
@include('emails.components.button', [
    'url' => $actionUrl,
    'text' => 'View Billing History & Subscriptions',
    'style' => 'primary'
])
@endif

@include('emails.components.security-notice', [
    'notice' => 'Receipts and billing history can be downloaded anytime from your O SAFE Account settings.'
])
@endsection
