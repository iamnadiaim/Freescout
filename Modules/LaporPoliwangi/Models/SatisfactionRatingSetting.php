<?php

namespace Modules\LaporPoliwangi\Models;

use App\Mailbox;
use Illuminate\Database\Eloquent\Model;

class SatisfactionRatingSetting extends Model
{
    protected $table = 'satisfaction_rating_settings';

    protected $fillable = [
        'mailbox_id',

        // Tab Settings
        'enabled',
        'add_ratings_mode',
        'placement',
        'ratings_text',
        'saving_mode',

        // Tab Translate / Language
        'page_title',
        'header',
        'great_text',
        'okay_text',
        'not_good_text',
        'comment_box_text',
        'comment_placeholder',
        'send_button_text',
        'send_confirmation_text',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Relasi ke mailbox.
     */
    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class, 'mailbox_id');
    }

    /**
     * Ambil default setting Satisfaction Ratings.
     */
    public static function defaultValues()
    {
        return [
            'enabled' => false,

            // all / shortcode
            'add_ratings_mode' => 'all',

            // above / below
            'placement' => 'above',

            'ratings_text' => 'How would you rate my reply?',

            // immediate / after_send
            'saving_mode' => 'immediate',

            // Translate / Language
            'page_title' => 'Satisfaction Ratings',
            'header' => 'Thanks for your rating!',
            'great_text' => 'Great',
            'okay_text' => 'Okay',
            'not_good_text' => 'Not Good',
            'comment_box_text' => 'Would you like to share any other comments?',
            'comment_placeholder' => '(optional)',
            'send_button_text' => 'Send',
            'send_confirmation_text' => 'Feedback sent',
        ];
    }
}
