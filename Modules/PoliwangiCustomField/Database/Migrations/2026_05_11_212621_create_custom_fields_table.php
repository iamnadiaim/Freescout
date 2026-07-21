<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomFieldsTable extends Migration
{
    public function up()
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama_field');
            $table->string('type_field');
            $table->json('options')->nullable();
            $table->unsignedInteger('mailbox_id');
            $table->boolean('show_in_conversation_list')->default(false);
            $table->boolean('required')->default(false);

            $table->foreign('mailbox_id')
                ->references('id')
                ->on('mailboxes')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('custom_fields');
    }
}
