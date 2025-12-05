<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRecipient extends Model
{
    protected $table = 'notification_recipients';

    protected $fillable = [
        'notification_id',
        'user_id',
        'is_read',
        'read_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}

