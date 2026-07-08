<div class="modal fade" id="savedReplyImageModal" tabindex="-1" role="dialog"
    aria-labelledby="savedReplyImageModalLabel">
    <div class="modal-dialog saved-reply-image-modal" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>

                <h4 class="modal-title" id="savedReplyImageModalLabel">
                    Insert Image
                </h4>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label>Select from files</label>
                    <input type="file" id="saved-reply-image-file" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" id="saved-reply-image-url" class="form-control">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="saved-reply-insert-image-btn" class="btn btn-primary">
                    Insert Image
                </button>
            </div>

        </div>
    </div>
</div>
