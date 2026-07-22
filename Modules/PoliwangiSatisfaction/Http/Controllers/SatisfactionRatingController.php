<?php

namespace Modules\PoliwangiSatisfaction\Http\Controllers;

use Illuminate\Http\Request;
use App\Mailbox;
use App\Email;
use App\Conversation;
use App\Thread;
use Illuminate\Routing\Controller;
use Modules\PoliwangiSatisfaction\Models\SatisfactionRating;
use Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting;

class SatisfactionRatingController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    private function authorizeSettings(Mailbox $mailbox)
    {
        $user = auth()->user();
        $canAccess = $user->can('updateSettings', $mailbox);
        
        if (!$canAccess) {
            abort(403, 'Unauthorized action.');
        }
    }
    /**
     * Menampilkan halaman Satisfaction Ratings settings.
     */
    public function index($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorizeSettings($mailbox);

        /*
         * Ambil atau buat setting default untuk mailbox ini.
         */
        $setting = SatisfactionRatingSetting::firstOrCreate(
            [
                'mailbox_id' => $mailbox->id,
            ],
            SatisfactionRatingSetting::defaultValues()
        );

        return view('poliwangisatisfaction::satisfaction_ratings.index', compact(
            'mailbox',
            'setting'
        ));
    }

    public function updateSettings(Request $request, $mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorizeSettings($mailbox);

        $request->merge([
            'enabled' => $request->has('enabled'),
        ]);

        $request->validate(
            [
                'enabled' => 'nullable',
                'add_ratings_mode' => 'required|string|max:20|in:all,shortcode',
                'placement' => 'required|string|max:20|in:above,below',
                'ratings_text' => 'nullable|string',
                'saving_mode' => 'required|string|max:20|in:immediate,after_send',
            ],
            [
                'add_ratings_mode.required' => 'Please select how ratings should be added.',
                'add_ratings_mode.in' => 'The selected add ratings mode is invalid.',

                'placement.required' => 'The placement field is required.',
                'placement.in' => 'The selected placement is invalid.',

                'ratings_text.string' => 'The ratings text must be a valid text.',

                'saving_mode.required' => 'Please select how ratings should be saved.',
                'saving_mode.in' => 'The selected saving mode is invalid.',
            ]
        );

        $setting = SatisfactionRatingSetting::firstOrCreate(
            [
                'mailbox_id' => $mailbox->id,
            ],
            SatisfactionRatingSetting::defaultValues()
        );

        $setting->update([
            'enabled' => (bool) $request->enabled,
            'add_ratings_mode' => $request->add_ratings_mode,
            'placement' => $request->placement,
            'ratings_text' => $request->ratings_text,
            'saving_mode' => $request->saving_mode,
        ]);

        return redirect()
            ->to(route('PoliwangiPortal.satisfaction_ratings.index', ['mailbox_id' => $mailbox->id]) . '?tab=settings')
            ->with('success', 'Satisfaction Ratings settings saved successfully.');
    }

    /**
     * Save Translate / Language tab.
     */
    public function updateTranslate(Request $request, $mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorizeSettings($mailbox);

        $request->validate(
            [
                'page_title'             => 'required|string|max:191',
                'header'                 => 'required|string|max:191',
                'great_text'             => 'required|string|max:191',
                'okay_text'              => 'required|string|max:191',
                'not_good_text'          => 'required|string|max:191',
                'comment_box_text'       => 'nullable|string|max:65535',
                'comment_placeholder'    => 'nullable|string|max:65535',
                'send_button_text'       => 'required|string|max:191',
                'send_confirmation_text' => 'required|string|max:191',
            ],
            [
                'page_title.required' => 'The page title field is required.',
                'page_title.string'   => 'The page title must be a valid text.',
                'page_title.max'      => 'The page title may not be greater than 191 characters.',

                'header.required' => 'The header field is required.',
                'header.string'   => 'The header must be a valid text.',
                'header.max'      => 'The header may not be greater than 191 characters.',

                'great_text.required' => 'The Great rating text field is required.',
                'great_text.string'   => 'The Great rating text must be a valid text.',
                'great_text.max'      => 'The Great rating text may not be greater than 191 characters.',

                'okay_text.required' => 'The Okay rating text field is required.',
                'okay_text.string'   => 'The Okay rating text must be a valid text.',
                'okay_text.max'      => 'The Okay rating text may not be greater than 191 characters.',

                'not_good_text.required' => 'The Not Good rating text field is required.',
                'not_good_text.string'   => 'The Not Good rating text must be a valid text.',
                'not_good_text.max'      => 'The Not Good rating text may not be greater than 191 characters.',

                'comment_box_text.string' => 'The comment box text must be a valid text.',
                'comment_box_text.max'    => 'The comment box text may not be greater than 65535 characters.',

                'comment_placeholder.string' => 'The comment placeholder must be a valid text.',
                'comment_placeholder.max'    => 'The comment placeholder may not be greater than 65535 characters.',

                'send_button_text.required' => 'The send button text field is required.',
                'send_button_text.string'   => 'The send button text must be a valid text.',
                'send_button_text.max'      => 'The send button text may not be greater than 191 characters.',

                'send_confirmation_text.required' => 'The confirmation text field is required.',
                'send_confirmation_text.string'   => 'The confirmation text must be a valid text.',
                'send_confirmation_text.max'      => 'The confirmation text may not be greater than 191 characters.',
            ]
        );

        $setting = SatisfactionRatingSetting::firstOrCreate(
            [
                'mailbox_id' => $mailbox->id,
            ],
            SatisfactionRatingSetting::defaultValues()
        );

        $setting->update([
            'page_title'             => $request->page_title,
            'header'                 => $request->header,
            'great_text'             => $request->great_text,
            'okay_text'              => $request->okay_text,
            'not_good_text'          => $request->not_good_text,
            'comment_box_text'       => $request->comment_box_text,
            'comment_placeholder'    => $request->comment_placeholder,
            'send_button_text'       => $request->send_button_text,
            'send_confirmation_text' => $request->send_confirmation_text,
        ]);

        return redirect()
            ->to(route('PoliwangiPortal.satisfaction_ratings.index', ['mailbox_id' => $mailbox->id]) . '?tab=translate')
            ->with('success', 'Satisfaction Ratings language texts saved successfully.');
    }

    /**
     * Reset hanya tab Settings ke default.
     * Translate / Language tidak ikut berubah.
     */
    public function resetSettingsDefaults($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorize('update', $mailbox);
        $this->authorizeSettings($mailbox);

        $setting = SatisfactionRatingSetting::firstOrCreate(
            [
                'mailbox_id' => $mailbox->id,
            ],
            SatisfactionRatingSetting::defaultValues()
        );

        $defaults = SatisfactionRatingSetting::defaultValues();

        $setting->update([
            'enabled' => $defaults['enabled'],
            'add_ratings_mode' => $defaults['add_ratings_mode'],
            'placement' => $defaults['placement'],
            'ratings_text' => $defaults['ratings_text'],
            'saving_mode' => $defaults['saving_mode'],
        ]);

        return redirect()
            ->to(route('PoliwangiPortal.satisfaction_ratings.index', ['mailbox_id' => $mailbox->id]) . '?tab=settings')
            ->with('success', 'Settings have been reset to default.');
    }

    /**
     * Reset hanya tab Translate / Language ke default.
     * Settings tidak ikut berubah.
     */
    public function resetTranslateDefaults($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorize('update', $mailbox);
        $this->authorizeSettings($mailbox);

        $setting = SatisfactionRatingSetting::firstOrCreate(
            [
                'mailbox_id' => $mailbox->id,
            ],
            SatisfactionRatingSetting::defaultValues()
        );

        $defaults = SatisfactionRatingSetting::defaultValues();

        $setting->update([
            'page_title' => $defaults['page_title'],
            'header' => $defaults['header'],
            'great_text' => $defaults['great_text'],
            'okay_text' => $defaults['okay_text'],
            'not_good_text' => $defaults['not_good_text'],
            'comment_box_text' => $defaults['comment_box_text'],
            'comment_placeholder' => $defaults['comment_placeholder'],
            'send_button_text' => $defaults['send_button_text'],
            'send_confirmation_text' => $defaults['send_confirmation_text'],
        ]);

        return redirect()
            ->to(route('PoliwangiPortal.satisfaction_ratings.index', ['mailbox_id' => $mailbox->id]) . '?tab=translate')
            ->with('success', 'Translate texts have been reset to default.');
    }

    public function submitRating(Request $request, $mailbox_id, $conversation_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $setting = SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();

        if (!$setting || !$setting->enabled) {
            return redirect()
                ->route('PoliwangiPortal.end_user_portal.ticket_detail', [$mailbox->id, $conversation_id])
                ->withErrors([
                    'rating' => 'Satisfaction Ratings are currently disabled.',
                ]);
        }

        /*
     * End User Portal login uses global session keys.
     * Do not use end_user_portal_email_{mailbox_id}.
     */
        if (!session()->has('end_user_portal_email')) {
            return redirect()
                ->route('PoliwangiPortal.end_user_portal.login_end_user', [
                    'redirect' => route('PoliwangiPortal.end_user_portal.ticket_detail', [
                        $mailbox->id,
                        $conversation_id,
                    ]),
                ])
                ->withErrors([
                    'email' => 'Please log in before submitting a rating.',
                ]);
        }

        $email = strtolower(trim((string) session('end_user_portal_email')));

        $request->validate(
            [
                'rating'      => 'required|string|in:great,okay,not_good',
                'comment'     => 'nullable|string|max:1000',
                'thread_id'   => 'nullable|integer',
                'saving_mode' => 'nullable|string|in:immediate,after_send',
            ],
            [
                'rating.required' => 'Please select a rating.',
                'rating.in'       => 'The selected rating is invalid.',

                'comment.string' => 'The comment must be a valid text.',
                'comment.max'    => 'The comment may not be greater than 1000 characters.',

                'thread_id.integer' => 'The selected thread is invalid.',
                'saving_mode.in'    => 'The selected saving mode is invalid.',
            ]
        );

        /*
     * Get the conversation first.
     */
        $conversation = Conversation::where('id', $conversation_id)
            ->where('mailbox_id', $mailbox->id)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->first();

        if (!$conversation) {
            return redirect()
                ->route('PoliwangiPortal.end_user_portal.my_ticket')
                ->withErrors([
                    'rating' => 'The selected ticket was not found.',
                ]);
        }

        /*
     * Validate that this ticket belongs to the logged-in portal user.
     */
        $customerIds = Email::whereRaw('LOWER(email) = ?', [$email])
            ->pluck('customer_id')
            ->toArray();

        if (!in_array($conversation->customer_id, $customerIds)) {
            return redirect()
                ->route('PoliwangiPortal.end_user_portal.my_ticket')
                ->withErrors([
                    'rating' => 'You are not allowed to rate this ticket.',
                ]);
        }

        /*
     * Validate thread.
     */
        $threadId = $request->thread_id;

        if ($threadId) {
            $thread = Thread::where('id', $threadId)
                ->where('conversation_id', $conversation->id)
                ->where('state', Thread::STATE_PUBLISHED)
                ->first();

            if (!$thread) {
                return redirect()
                    ->route('PoliwangiPortal.end_user_portal.ticket_detail', [$mailbox->id, $conversation->id])
                    ->withErrors([
                        'rating' => 'The selected reply was not found.',
                    ]);
            }

            $threadId = $thread->id;
        } else {
            $threadId = null;
        }

        /*
     * Save or update rating.
     */
        SatisfactionRating::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'thread_id'      => $threadId,
                'email'          => $email,
            ],
            [
                'mailbox_id'   => $mailbox->id,
                'customer_id'  => $conversation->customer_id,
                'rating'       => $request->rating,
                'comment'      => $request->comment,
            ]
        );

        return redirect()
            ->route('PoliwangiPortal.end_user_portal.ticket_detail', [$mailbox->id, $conversation->id])
            ->with(
                'success',
                $setting->send_confirmation_text ?: 'Your rating has been submitted. Thank you for your feedback.'
            );
    }

    /**
     * Menampilkan daftar hasil rating untuk mailbox.
     */
    public function report($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorize('view', $mailbox);
        $this->authorizeSettings($mailbox);

        $ratings = SatisfactionRating::with([
            'conversation',
            'thread',
            'customer',
        ])
            ->where('mailbox_id', $mailbox->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('poliwangisatisfaction::satisfaction_ratings.report', compact(
            'mailbox',
            'ratings'
        ));
    }
    public function rateFromEmail(Request $request, $mailbox_id, $token, $rating)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $setting = SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();

        if (!$setting || !$setting->enabled) {
            abort(404);
        }

        if (!in_array($rating, ['great', 'okay', 'not_good'])) {
            abort(404);
        }

        $conversation = Conversation::where('satisfaction_rating_token', $token)
            ->where('mailbox_id', $mailbox->id)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->first();

        if (!$conversation) {
            abort(404, 'Invalid or expired rating link.');
        }

        // Token is verified! Find the latest published thread for this conversation to associate the rating.
        $thread = Thread::where('conversation_id', $conversation->id)
            ->where('state', Thread::STATE_PUBLISHED)
            ->where('type', Thread::TYPE_MESSAGE)
            ->orderBy('id', 'desc')
            ->first();

        if (!$thread) {
            abort(404);
        }

        $threadId = $thread->id;
        $email = $conversation->customer->email;

        // Check if rating exists
        $existing = SatisfactionRating::where('conversation_id', $conversation->id)->first();

        SatisfactionRating::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'thread_id'      => $threadId,
                'email'          => $email,
            ],
            [
                'mailbox_id'  => $mailbox->id,
                'customer_id' => $conversation->customer_id,
                'rating'      => $rating,
                'comment'     => null,
            ]
        );

        return view('poliwangisatisfaction::satisfaction_ratings.thank_you', [
            'mailbox' => $mailbox,
            'setting' => $setting,
            'rating'  => $rating,
        ]);
    }
}
