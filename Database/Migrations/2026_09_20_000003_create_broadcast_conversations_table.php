<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastConversationsTable extends Migration
{
    /**
     * Marks a FreeScout conversation as a broadcast sent to a recipient list.
     * Its presence is what tells the module to take over sending for that
     * conversation instead of FreeScout's normal single-recipient reply job.
     */
    public function up()
    {
        Schema::create('broadcast_conversations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('conversation_id')->unique();
            $table->integer('broadcast_list_id')->nullable();
            $table->timestamps();

            $table->foreign('broadcast_list_id')->references('id')->on('broadcast_lists')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_conversations');
    }
}
