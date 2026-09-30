<?php

namespace App\Models\Support;

use App\Enums\SupportTicketStatus;
use App\Models\Admin\Staff;
use App\Models\Device\Device;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\SupportTicketFactory::new();
    }

    protected $table = 'support_tickets';
    protected $primaryKey = 'ticket_id';

    protected $fillable = [
        'ticket_number',
        'user_id',
        'assigned_to',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'resolution_note',
        'device_id',
        'first_response_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => SupportTicketStatus::class,
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to', 'staff_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id', 'ticket_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            SupportTicketStatus::Open,
            SupportTicketStatus::InProgress,
            SupportTicketStatus::WaitingUser,
        ]);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }
}
