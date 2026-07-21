<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimeTrackingLogsTable extends Migration
{
    public function up()
    {
        Schema::create('time_tracking_logs', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('conversation_id');
            $table->unsignedInteger('mailbox_id')->nullable();
            $table->unsignedInteger('user_id');

            $table->integer('seconds')->default(0);
            $table->string('source')->default('manual'); // timer, manual, auto
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index('conversation_id');
            $table->index('mailbox_id');
            $table->index('user_id');
            $table->index('source');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('time_tracking_logs');
    }
}
