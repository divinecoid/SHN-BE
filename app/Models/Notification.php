<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'title',
        'message',
        'type',
        'created_by'
    ];

    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class, 'notification_id');
    }
}

