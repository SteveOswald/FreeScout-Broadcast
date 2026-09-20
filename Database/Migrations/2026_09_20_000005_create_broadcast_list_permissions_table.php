<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastListPermissionsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('broadcast_list_permissions')) {
            return;
        }

        Schema::create('broadcast_list_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('broadcast_list_id');
            $table->unsignedInteger('user_id');
            // May edit the list's name/description/mailbox/members.
            $table->boolean('can_edit')->default(false);
            // May select the list as a recipient when composing (also true when can_edit is true).
            $table->boolean('can_use')->default(false);
            $table->timestamps();

            $table->unique(['broadcast_list_id', 'user_id'], 'broadcast_list_permissions_list_user_unique');
            $table->foreign('broadcast_list_id')->references('id')->on('broadcast_lists')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_list_permissions');
    }
}
