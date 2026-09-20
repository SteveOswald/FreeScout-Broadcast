<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastSendsTable extends Migration
{
    /**
     * One row per individual recipient of a broadcast conversation.
     * message_id stores the unique, module-minted Message-ID used to send
     * that recipient's copy, which is later used to recognize (defensively)
     * an incoming reply as belonging to a broadcast rather than a real thread.
     */
    public function up()
    {
        if (Schema::hasTable('broadcast_sends')) {
            return;
        }

        Schema::create('broadcast_sends', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('broadcast_conversation_id');
            $table->string('email', 191);
            $table->string('name', 191)->nullable();
            $table->string('message_id', 191)->nullable();
            // 0 = pending, 1 = sent, 2 = failed
            $table->unsignedTinyInteger('status')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique('message_id', 'broadcast_sends_message_id_unique');
            $table->index(['broadcast_conversation_id', 'status']);
            $table->foreign('broadcast_conversation_id')->references('id')->on('broadcast_conversations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_sends');
    }
}
