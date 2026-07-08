<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEndUserPortalSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('end_user_portal_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('mailbox_id')->unique();
            $table->string('portal_url')->nullable();
            $table->string('submit_ticket_title')->default('Submit a Ticket');
            $table->text('custom_fields')->nullable();
            $table->boolean('subject_field')->default(false);
            $table->boolean('consent_checkbox')->default(false);
            $table->boolean('show_ticket_numbers')->default(false);
            $table->longText('footer')->nullable();
            $table->boolean('only_existing_customers')->default(false);

            $table->timestamps();
            $table->foreign('mailbox_id')
                ->references('id')
                ->on('mailboxes')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('end_user_portal_settings');
    }
}
