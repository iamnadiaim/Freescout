<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSatisfactionRatingSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('satisfaction_rating_settings', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Mailbox yang punya setting satisfaction rating
            $table->unsignedInteger('mailbox_id');

            /*
             * TAB SETTINGS
             */

            // Enable Ratings
            $table->boolean('enabled')->default(false);

            // Add Ratings:
            // all = add to all replies
            // shortcode = hanya jika ada shortcode
            $table->string('add_ratings_mode', 20)->default('all');

            // Placement:
            // above = above mailbox signature
            // below = below mailbox signature
            $table->string('placement', 20)->default('above');

            // Ratings Text
            $table->longText('ratings_text')->nullable();

            // Saving Mode:
            // immediate = langsung simpan setelah klik rating
            // after_send = simpan setelah tombol Send diklik
            $table->string('saving_mode', 20)->default('immediate');

            /*
             * TAB TRANSLATE / LANGUAGE
             */

            $table->string('page_title')->default('Satisfaction Ratings');
            $table->string('header')->default('Thanks for your rating!');

            $table->string('great_text')->default('Great');
            $table->string('okay_text')->default('Okay');
            $table->string('not_good_text')->default('Not Good');

            $table->text('comment_box_text')->nullable();
            $table->text('comment_placeholder')->nullable();

            $table->string('send_button_text')->default('Send');
            $table->string('send_confirmation_text')->default('Feedback sent');

            $table->timestamps();

            $table->unique('mailbox_id');
            $table->index('enabled');
        });
    }

    public function down()
    {
        Schema::dropIfExists('satisfaction_rating_settings');
    }
}
