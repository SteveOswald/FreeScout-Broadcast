<?php

namespace Modules\Broadcast\Entities;

use Illuminate\Database\Eloquent\Model;

class BroadcastListPermission extends Model
{
    protected $fillable = ['broadcast_list_id', 'user_id', 'can_edit', 'can_use'];

    protected $casts = [
        'can_edit' => 'boolean',
        'can_use' => 'boolean',
    ];

    public function list()
    {
        return $this->belongsTo(BroadcastList::class, 'broadcast_list_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }
}
