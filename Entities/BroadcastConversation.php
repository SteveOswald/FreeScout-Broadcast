<?php

namespace Modules\Broadcast\Entities;

use App\Conversation;
use Illuminate\Database\Eloquent\Model;

class BroadcastConversation extends Model
{
    protected $fillable = ['conversation_id', 'broadcast_list_id'];

    public function list()
    {
        return $this->belongsTo(BroadcastList::class, 'broadcast_list_id');
    }

    public function sends()
    {
        return $this->hasMany(BroadcastSend::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }
}
