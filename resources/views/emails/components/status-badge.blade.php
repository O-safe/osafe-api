@props(['status' => 'active', 'label' => null])

@php
    $style = match(strtolower($status)) {
        'active', 'verified', 'completed', 'success', 'paid' => 'background-color: #dcfce7; color: #15803d; border: 1px solid #86efac;',
        'locked', 'expired', 'failed', 'cancelled', 'danger' => 'background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;',
        'pending', 'warning', 'renewing' => 'background-color: #fef3c7; color: #b45309; border: 1px solid #fde047;',
        default => 'background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;'
    };
    $displayText = strtoupper($label ?? $status);
@endphp

<span style="display: inline-block; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 12px; letter-spacing: 0.8px; {{ $style }}">
    {{ $displayText }}
</span>
