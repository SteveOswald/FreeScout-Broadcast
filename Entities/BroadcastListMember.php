<?php

namespace Modules\Broadcast\Entities;

use Illuminate\Database\Eloquent\Model;

class BroadcastListMember extends Model
{
    protected $fillable = ['broadcast_list_id', 'email', 'name'];

    public function list()
    {
        return $this->belongsTo(BroadcastList::class, 'broadcast_list_id');
    }
}
