<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimeTrackingSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('time_tracking_sessions', function (Blueprint $table) {
            $table->increments('id');

            // Relasi utama
            $table->unsignedInteger('conversation_id');
            $table->unsignedInteger('mailbox_id');
            $table->unsignedInteger('user_id');

            // Timer aktif dihitung dari started_at
            $table->timestamp('started_at')->nullable();

            // Total detik sementara jika timer pernah di-pause
            $table->integer('elapsed_seconds')->default(0);

            // running / paused / stopped
            $table->string('status', 20)->default('running');

            // sumber start timer: reply, manual, view, dll
            $table->string('source', 50)->default('reply');

            // optional untuk tracking reply mana yang memulai timer
            $table->unsignedInteger('thread_id')->nullable();

            $table->timestamps();

            // Satu user hanya punya satu session timer per conversation
            $table->unique(['conversation_id', 'user_id'], 'tt_session_conv_user_unique');

            $table->index('mailbox_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('time_tracking_sessions');
    }
}
