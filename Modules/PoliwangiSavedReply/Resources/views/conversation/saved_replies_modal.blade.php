

<div class="modal fade" id="saveThisReplyModal" tabindex="-1" role="dialog"
    aria-labelledby="saveThisReplyModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <form method="POST" action="{{ route('poliwangisavedreply.saved_replies.store', ['id' => $mailbox->id]) }}">
                {{ csrf_field() }}

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <h4 class="modal-title" id="saveThisReplyModalLabel">
                        Save This Reply
                    </h4>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" id="save-this-reply-name" class="form-control" required>
                    </div>

                    @if ($saved_reply_categories->count())
                        <div class="form-group">
                            <label>Category</label>

                            <select name="parent_id" id="save-this-reply-parent" class="form-control" required>
                                <option value="">-- Select Category --</option>

                                @foreach ($saved_reply_categories as $category)
                                    @if ($category->mailbox_id == $mailbox->id)
                                        <option value="{{ $category->id }}">
                                            {{ $category->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group">
                        <label>Reply</label>
                        <textarea name="reply" id="save-this-reply-body" class="form-control" rows="8" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="is_global" value="1">
                            Global
                        </label>

                        <p class="text-muted" style="margin-top: 6px;">
                            If Global is enabled, this saved reply can be used in all mailboxes when replying.
                            If it is disabled, it will only appear in this mailbox.
                        </p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Save Reply
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
