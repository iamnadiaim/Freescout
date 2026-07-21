<?php

namespace Modules\PoliwangiSavedReply\Http\Controllers;

use App\Mailbox;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\PoliwangiSavedReply\Models\SavedReply;

class SavedRepliesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Authorize Saved Replies access.
     */

    private function authorizeSavedReplies(Mailbox $mailbox)
    {
        /** @var \App\User|null $user */
        $user = auth()->user();

        if ($user->isAdmin()) {
            return;
        }

        if (!$user->hasAccessToMailbox($mailbox->id)) {
            abort(403);
        }

        if (!$user->hasPermission(User::PERM_EDIT_SAVED_REPLIES) && !$user->hasManageMailboxPermission($mailbox->id, Mailbox::ACCESS_PERM_EDIT)) {
            abort(403);
        }
    }

    /**
     * Saved Replies page inside mailbox.
     */
    public function index($id)
    {
        $mailbox = Mailbox::findOrFail($id);

        $this->authorizeSavedReplies($mailbox);

        $saved_replies = SavedReply::with(['parent', 'children', 'user'])
            ->where('mailbox_id', $mailbox->id)
            ->whereNull('parent_id')
            ->orderBy('name', 'asc')
            ->get();

        $parents = SavedReply::where('mailbox_id', $mailbox->id)
            ->whereNull('parent_id')
            ->orderBy('name', 'asc')
            ->get();

        return view('poliwangisavedreply::saved_replies', [
            'mailbox'       => $mailbox,
            'saved_replies' => $saved_replies,
            'parents'       => $parents,
        ]);
    }

    /**
     * Store a new Saved Reply.
     */
    public function store(Request $request, $id)
    {
        $mailbox = Mailbox::findOrFail($id);

        $this->authorizeSavedReplies($mailbox);

        $request->merge([
            'name'      => trim((string) $request->name),
            'reply'     => trim((string) $request->reply),
            'parent_id' => $request->parent_id ?: null,
            'is_global' => $request->filled('is_global'),
        ]);

        $validator = Validator::make(
            $request->all(),
            [
                'name'      => 'required|string|min:2|max:191',
                'reply'     => 'nullable|string',
                'parent_id' => 'nullable|integer|exists:saved_replies,id',
                'is_global' => 'boolean',
            ],
            [
                'name.required' => 'The name field is required.',
                'name.string'   => 'The name must be a valid text.',
                'name.min'      => 'The name must be at least 2 characters.',
                'name.max'      => 'The name may not be greater than 191 characters.',

                'reply.string' => 'The reply must be a valid text.',

                'parent_id.integer' => 'The selected category is invalid.',
                'parent_id.exists'  => 'The selected category does not exist.',

                'is_global.boolean' => 'The global field must be true or false.',
            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->route('poliwangisavedreply.saved_replies', ['id' => $mailbox->id])
                ->withErrors($validator)
                ->withInput();
        }

        $parentId = null;

        /*
         * If parent_id is filled, this item is a child saved reply.
         * The selected parent must be a top-level saved reply in the same mailbox.
         */
        if ($request->filled('parent_id')) {
            $parent = SavedReply::where('mailbox_id', $mailbox->id)
                ->whereNull('parent_id')
                ->where('id', $request->parent_id)
                ->first();

            if (!$parent) {
                return redirect()
                    ->route('poliwangisavedreply.saved_replies', ['id' => $mailbox->id])
                    ->withErrors([
                        'parent_id' => 'The selected category is invalid for this mailbox.',
                    ])
                    ->withInput();
            }

            $parentId = $parent->id;

            if (!$request->filled('reply')) {
                return redirect()
                    ->route('poliwangisavedreply.saved_replies', ['id' => $mailbox->id])
                    ->withErrors([
                        'reply' => 'The reply field is required when creating a saved reply inside a category.',
                    ])
                    ->withInput();
            }
        }

        $savedReply = new SavedReply();
        $savedReply->mailbox_id = $mailbox->id;
        $savedReply->parent_id = $parentId;
        $savedReply->name = $request->name;
        $savedReply->reply = $request->filled('reply') ? $request->reply : null;
        $savedReply->is_global = (bool) $request->is_global;
        $savedReply->user_id = auth()->id();
        $savedReply->save();

        /*
         * If this saved reply is created inside another saved reply,
         * the selected parent becomes a category.
         * Therefore, clear the parent's reply content.
         */
        if ($parentId) {
            SavedReply::where('mailbox_id', $mailbox->id)
                ->where('id', $parentId)
                ->update([
                    'reply' => null,
                ]);
        }

        session()->flash(
            'flash_success_floating',
            __('Saved Reply created successfully')
        );

        return redirect()
            ->route('poliwangisavedreply.saved_replies', ['id' => $mailbox->id]);
    }

    /**
     * Update Saved Reply.
     */
    public function update(Request $request, $id, $reply_id)
    {
        $mailbox = Mailbox::findOrFail($id);

        $this->authorizeSavedReplies($mailbox);

        $savedReply = SavedReply::where('mailbox_id', $mailbox->id)
            ->where('id', $reply_id)
            ->firstOrFail();

        $hasChildren = SavedReply::where('mailbox_id', $mailbox->id)
            ->where('parent_id', $savedReply->id)
            ->exists();

        $request->merge([
            'name'      => trim((string) $request->name),
            'reply'     => trim((string) $request->reply),
            'parent_id' => $request->parent_id ?: null,
            'is_global' => $request->filled('is_global'),
        ]);

        $validator = Validator::make(
            $request->all(),
            [
                'name'      => 'required|string|min:2|max:191',
                'reply'     => 'nullable|string',
                'parent_id' => 'nullable|integer|exists:saved_replies,id',
                'is_global' => 'boolean',
            ],
            [
                'name.required' => 'The name field is required.',
                'name.string'   => 'The name must be a valid text.',
                'name.min'      => 'The name must be at least 2 characters.',
                'name.max'      => 'The name may not be greater than 191 characters.',

                'reply.string' => 'The reply must be a valid text.',

                'parent_id.integer' => 'The selected category is invalid.',
                'parent_id.exists'  => 'The selected category does not exist.',

                'is_global.boolean' => 'The global field must be true or false.',
            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->route('poliwangisavedreply.saved_replies', ['id' => $mailbox->id])
                ->withErrors($validator)
                ->withInput();
        }

        /*
         * Prevent a saved reply from selecting itself as a category.
         */
        if (
            $request->filled('parent_id') &&
            (int) $request->parent_id === (int) $savedReply->id
        ) {
            return redirect()
                ->route('poliwangisavedreply.saved_replies', ['id' => $mailbox->id])
                ->withErrors([
                    'parent_id' => 'A saved reply cannot select itself as a category.',
                ])
                ->withInput();
        }

        /*
         * If this saved reply already has children,
         * it must stay as a top-level category.
         */
        if ($hasChildren) {
            $parentId = null;
            $replyContent = null;
        } else {
            $parentId = null;

            if ($request->filled('parent_id')) {
                $parent = SavedReply::where('mailbox_id', $mailbox->id)
                    ->whereNull('parent_id')
                    ->where('id', $request->parent_id)
                    ->first();

                if (!$parent) {
                    return redirect()
                        ->route('poliwangisavedreply.saved_replies', ['id' => $mailbox->id])
                        ->withErrors([
                            'parent_id' => 'The selected category is invalid for this mailbox.',
                        ])
                        ->withInput();
                }

                $parentId = $parent->id;

                if (!$request->filled('reply')) {
                    return redirect()
                        ->route('poliwangisavedreply.saved_replies', ['id' => $mailbox->id])
                        ->withErrors([
                            'reply' => 'The reply field is required when this saved reply is placed inside a category.',
                        ])
                        ->withInput();
                }
            }

            $replyContent = $request->filled('reply') ? $request->reply : null;
        }

        $oldParentId = $savedReply->parent_id;

        $savedReply->parent_id = $parentId;
        $savedReply->name = $request->name;
        $savedReply->reply = $replyContent;
        $savedReply->is_global = (bool) $request->is_global;
        $savedReply->save();

        /*
         * If this saved reply is moved into a parent,
         * the selected parent becomes a category.
         */
        if ($parentId) {
            SavedReply::where('mailbox_id', $mailbox->id)
                ->where('id', $parentId)
                ->update([
                    'reply' => null,
                ]);
        }

        /*
         * If the old parent still has children,
         * make sure the old parent remains a category.
         */
        if ($oldParentId && $oldParentId != $parentId) {
            $oldParentStillHasChildren = SavedReply::where('mailbox_id', $mailbox->id)
                ->where('parent_id', $oldParentId)
                ->exists();

            if ($oldParentStillHasChildren) {
                SavedReply::where('mailbox_id', $mailbox->id)
                    ->where('id', $oldParentId)
                    ->update([
                        'reply' => null,
                    ]);
            }
        }

        session()->flash(
            'flash_success_floating',
            __('Saved Reply updated successfully')
        );

        return redirect()
            ->route('poliwangisavedreply.saved_replies', ['id' => $mailbox->id]);
    }

    /**
     * Delete Saved Reply.
     */
    public function destroy($id, $reply_id)
    {
        $mailbox = Mailbox::findOrFail($id);

        $this->authorizeSavedReplies($mailbox);

        $savedReply = SavedReply::where('mailbox_id', $mailbox->id)
            ->where('id', $reply_id)
            ->firstOrFail();

        /*
         * If the deleted saved reply is a category,
         * delete all of its children as well.
         */
        SavedReply::where('mailbox_id', $mailbox->id)
            ->where('parent_id', $savedReply->id)
            ->delete();

        $savedReply->delete();

        session()->flash(
            'flash_success_floating',
            __('Saved Reply deleted successfully')
        );

        return redirect()
            ->route('poliwangisavedreply.saved_replies', ['id' => $mailbox->id]);
    }
}
