<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastListMembersTable extends Migration
{
    public function up()
    {
        Schema::create('broadcast_list_members', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('broadcast_list_id');
            $table->string('email', 191);
            $table->string('name', 191)->nullable();
            $table->timestamps();

            $table->unique(['broadcast_list_id', 'email'], 'broadcast_list_members_list_email_unique');
            $table->foreign('broadcast_list_id')->references('id')->on('broadcast_lists')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_list_members');
    }
}
