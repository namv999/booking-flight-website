<?php

namespace App\Models;

use App\Notifications\AppNotification;
use Illuminate\Database\Eloquent\Model;

class NotificationBroadcast extends Model
{
    protected $fillable = ['admin_id', 'type', 'title', 'message', 'url', 'recipient_count'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return AppNotification::BROADCAST_TYPES[$this->type] ?? $this->type;
    }
}
