# FreeScout-Broadcast

Send bulk emails and newsletters directly from FreeScout with the Broadcast module. Message multiple recipients at once without exposing their email addresses to each other (BCC style). Perfect for secure customer updates and announcements.

## Features

- **Recipient lists** - create named lists of recipients (paste one address per line, or `Name <email@example.com>`), managed under *Manage → Recipient Lists* (admin only).
- **Pick a list when composing** - when starting a *New Conversation* of type Email, an extra "Recipient List" field lets you choose a saved list instead of (or in addition to) typing individual recipients.
- **True BCC-style privacy** - each recipient is sent their own individually addressed copy of the message. No recipient ever sees any other recipient's address, and real `Bcc:` headers are not used either (some mail clients still reveal Bcc'd addresses to the sender's other Bcc recipients via bounce/read receipts - this module avoids that entirely by sending one message per recipient).
- **Only one conversation** - no matter how many recipients are on the list, sending the broadcast creates exactly one conversation in FreeScout, attached to a lightweight per-list placeholder contact, so every past broadcast to the same list is grouped together.
- **Replies start fresh** - if a recipient replies to the email they received, FreeScout creates a brand new conversation for that reply. It is never threaded back into the original broadcast conversation, since each recipient's copy carries its own unique Message-ID that intentionally does not match FreeScout's reply-detection.

## Installation

1. Copy (or clone) this repository into your FreeScout installation's `Modules/Broadcast` directory, so that `Modules/Broadcast/module.json` exists.
2. From your FreeScout root, run:
   ```bash
   php artisan module:migrate Broadcast
   php artisan module:enable Broadcast
   php artisan cache:clear
   ```
3. Alternatively, go to *Manage → Modules* in the FreeScout UI and activate "Broadcast" from there (this also runs its migrations).

## Usage

1. Go to **Manage → Recipient Lists** and create a list: give it a name and paste in the recipient addresses (one per line).
2. Open a mailbox and start a **New Conversation** (email type).
3. In the new "Recipient List" field, pick your list instead of filling in the "To" field manually.
4. Write your subject and message as usual and click **Send**.
5. FreeScout shows a single conversation for the broadcast. Behind the scenes, every recipient on the list receives their own copy of the message with only their own address in "To".

## How it works

- Selecting a list attaches the new conversation to a persistent, non-routable placeholder address (`list-xxxx@broadcast.invalid`) unique to that list, so FreeScout's normal "one customer per conversation" model is satisfied and every broadcast to that list shows up in one place.
- Sending is intercepted via FreeScout's `conversation.skip_send_reply_to_customer` hook: FreeScout's own single-recipient email job is skipped, and the module instead queues one individually addressed email per list member (reusing FreeScout's own reply template, signature and attachment handling).
- Each of those emails gets its own unique Message-ID, minted with an `FS_broadcast-` prefix that is deliberately different from FreeScout's own `FS_reply-`/`FS_notify-`/`FS_autoreply-` prefixes used for reply detection. When a recipient replies, FreeScout's incoming mail matching does not recognize the Message-ID and creates a new conversation, exactly as it would for any first-time incoming email - no core changes required, and a `fetch_emails.data_to_save` hook is added as an extra safety net.

## Permissions

Managing recipient lists (create/edit/delete) is restricted to administrators. Any agent who can start a new conversation in a mailbox can pick an existing list when composing.

## Limitations (v1)

- CC/BCC fields in the compose form are ignored/cleared when a recipient list is selected, since they would defeat the privacy guarantee.
- Per-recipient send status (sent/failed) is tracked internally but has no dedicated UI yet; check `broadcast_sends` in the database or the FreeScout activity log for delivery errors.
- No CSV import - lists are managed via a plain-text textarea.
