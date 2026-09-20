<?php

namespace Modules\Broadcast\Entities;

use Illuminate\Database\Eloquent\Model;

class BroadcastSend extends Model
{
    const STATUS_PENDING = 0;
    const STATUS_SENT = 1;
    const STATUS_FAILED = 2;

    protected $fillable = ['broadcast_conversation_id', 'email', 'name', 'message_id', 'status', 'error', 'sent_at'];

    protected $dates = ['sent_at'];

    // Prefix used in the Message-ID we mint for each individual recipient email.
    // Deliberately distinct from FreeScout's own "FS_reply" / "FS_notify" /
    // "FS_autoreply" prefixes so that a recipient's reply never gets matched
    // back to the broadcast thread by FreeScout's own reply-detection regexes.
    const MESSAGE_ID_PREFIX = 'FS_broadcast';

    public function broadcastConversation()
    {
        return $this->belongsTo(BroadcastConversation::class);
    }

    public function getMessageIdHash()
    {
        return substr(md5('broadcast-'.$this->id.config('app.key')), 0, 16);
    }

    public function generateMessageId($domain)
    {
        return self::MESSAGE_ID_PREFIX.'-'.$this->id.'-'.$this->getMessageIdHash().'@'.$domain;
    }

    /**
     * Extract the broadcast send ID from a Message-ID minted by
     * generateMessageId(), verifying its hash. Returns null if the
     * Message-ID does not look like one of ours or the hash is invalid.
     */
    public static function idFromMessageId($message_id)
    {
        $message_id = trim($message_id ?? '', '<>');

        if (!preg_match('/^'.preg_quote(self::MESSAGE_ID_PREFIX, '/').'-(\d+)-([a-z0-9]+)@/', $message_id, $m)) {
            return null;
        }

        $send = self::find((int) $m[1]);
        if (!$send) {
            return null;
        }

        if ($m[2] !== $send->getMessageIdHash()) {
            return null;
        }

        return $send->id;
    }
}
