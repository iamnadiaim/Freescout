<div style="font-family: Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; margin-top: 20px; margin-bottom: 20px; color: #555555; text-align: center; border-top: 1px solid #eeeeee; padding-top: 20px;">
    <p style="font-size: 14px; font-weight: bold; margin-bottom: 15px;">
        {{ $setting->ratings_text }}
    </p>
    
    <table align="center" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
        <tr>
            <!-- Great -->
            <td align="center" style="padding: 0 10px;">
                <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('mailboxes.satisfaction_ratings.rate_from_email', ['mailbox_id' => $mailbox->id, 'conversation_id' => $conversation->id, 'thread_id' => $thread->id, 'rating' => 'great', 'email' => $conversation->customer_email]) }}" style="text-decoration: none; display: inline-block;">
                    <img src="https://img.icons8.com/color/48/000000/happy.png" alt="Great" width="48" height="48" style="display: block; border: 0;" />
                    <span style="font-size: 12px; color: #4CAF50; display: block; margin-top: 5px;">{{ $setting->great_text }}</span>
                </a>
            </td>
            
            <!-- Okay -->
            <td align="center" style="padding: 0 10px;">
                <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('mailboxes.satisfaction_ratings.rate_from_email', ['mailbox_id' => $mailbox->id, 'conversation_id' => $conversation->id, 'thread_id' => $thread->id, 'rating' => 'okay', 'email' => $conversation->customer_email]) }}" style="text-decoration: none; display: inline-block;">
                    <img src="https://img.icons8.com/color/48/000000/neutral-emoticon.png" alt="Okay" width="48" height="48" style="display: block; border: 0;" />
                    <span style="font-size: 12px; color: #FF9800; display: block; margin-top: 5px;">{{ $setting->okay_text }}</span>
                </a>
            </td>
            
            <!-- Not Good -->
            <td align="center" style="padding: 0 10px;">
                <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('mailboxes.satisfaction_ratings.rate_from_email', ['mailbox_id' => $mailbox->id, 'conversation_id' => $conversation->id, 'thread_id' => $thread->id, 'rating' => 'not_good', 'email' => $conversation->customer_email]) }}" style="text-decoration: none; display: inline-block;">
                    <img src="https://img.icons8.com/color/48/000000/sad.png" alt="Not Good" width="48" height="48" style="display: block; border: 0;" />
                    <span style="font-size: 12px; color: #F44336; display: block; margin-top: 5px;">{{ $setting->not_good_text }}</span>
                </a>
            </td>
        </tr>
    </table>
</div>
