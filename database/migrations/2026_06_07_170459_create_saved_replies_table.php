<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSavedRepliesTable extends Migration
{
    public function up()
    {
        Schema::create('saved_replies', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Mailbox asal saved reply.
            // Kalau is_global = 1, tetap boleh punya mailbox_id sebagai asal pembuat.
            $table->unsignedInteger('mailbox_id')->nullable();

            // Parent saved reply.
            // Jika parent_id null, berarti ini kategori/induk.
            // Jika parent_id ada, berarti ini child/balasan di dalam kategori.
            $table->unsignedBigInteger('parent_id')->nullable();

            // Nama saved reply / nama kategori
            $table->string('name');

            // Isi balasan.
            // Nullable karena parent/kategori tidak punya isi reply.
            $table->longText('reply')->nullable();

            // Global = dapat dipakai di semua mailbox saat membalas.
            $table->boolean('is_global')->default(false);

            // Pembuat saved reply
            $table->unsignedInteger('user_id')->nullable();

            $table->timestamps();

            $table->foreign('mailbox_id')
                ->references('id')
                ->on('mailboxes')
                ->onDelete('cascade');

            $table->foreign('parent_id')
                ->references('id')
                ->on('saved_replies')
                ->onDelete('set null');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('saved_replies');
    }
}
