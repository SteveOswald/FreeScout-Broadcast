<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastListsTable extends Migration
{
    public function up()
    {
        Schema::create('broadcast_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mailbox_id')->nullable();
            $table->string('name', 191);
            $table->text('description')->nullable();
            // Address of the persistent "virtual customer" every broadcast sent
            // to this list is attached to, so that past broadcasts to the same
            // list are grouped together in FreeScout.
            $table->string('virtual_email', 191)->unique();
            $table->integer('created_by_user_id')->nullable();
            $table->timestamps();

            $table->index('mailbox_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_lists');
    }
}
