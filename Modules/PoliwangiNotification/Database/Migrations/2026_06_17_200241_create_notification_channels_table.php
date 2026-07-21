<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationChannelsTable extends Migration
{
    public function up()
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Null berarti channel dapat digunakan secara global.
            $table->unsignedInteger('mailbox_id')->nullable();

            // Nama channel yang ditampilkan kepada admin.
            $table->string('name', 150);

            // Driver pengiriman: telegram, whatsapp, email, webhook, dll.
            $table->string('type', 50);

            // Konfigurasi berbeda untuk setiap jenis channel.
            $table->longText('config')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('mailbox_id');
            $table->index('type');
            $table->index('is_active');
            $table->index(['mailbox_id', 'type', 'is_active']);

            $table->foreign('mailbox_id')
                ->references('id')
                ->on('mailboxes')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_channels');
    }
}
