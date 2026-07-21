<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSatisfactionRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('satisfaction_ratings', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Mailbox tujuan
            $table->unsignedInteger('mailbox_id');

            // Conversation / ticket yang diberi rating
            $table->unsignedInteger('conversation_id');

            // Thread / balasan admin yang dinilai
            $table->unsignedInteger('thread_id')->nullable();

            // Customer / pelapor
            $table->unsignedInteger('customer_id')->nullable();

            // Email pelapor
            $table->string('email')->nullable();

            // Rating: great, okay, not_good
            $table->string('rating', 20);

            // Komentar tambahan dari pelapor
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->index('mailbox_id');
            $table->index('conversation_id');
            $table->index('thread_id');
            $table->index('customer_id');
            $table->index('email');

            // Biar 1 pelapor hanya punya 1 rating untuk 1 balasan/thread.
            // Kalau submit lagi, nanti datanya di-update.
            $table->unique([
                'conversation_id',
                'thread_id',
                'email',
            ], 'satisfaction_rating_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('satisfaction_ratings');
    }
}
