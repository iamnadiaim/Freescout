@php
    if (!isset($saved_reply_categories)) {
        $saved_reply_categories = collect();

        if (!empty($mailbox)) {
            $saved_reply_categories = \Modules\PoliwangiSavedReply\Models\SavedReply::with([
                'children' => function ($q) use ($mailbox) {
                    $q->where(function ($query) use ($mailbox) {
                        $query->where('mailbox_id', $mailbox->id)
                            ->orWhere('is_global', 1);
                    })->orderBy('name', 'asc');
                },
            ])
                ->whereNull('parent_id')
                ->where(function ($q) use ($mailbox) {
                    $q->where('mailbox_id', $mailbox->id)
                        ->orWhere('is_global', 1);
                })
                ->orderBy('name', 'asc')
                ->get();
        }
    }
@endphp
{{-- Saved Replies Toolbar Dropdown --}}
                                <div class="saved-replies-wrapper js-saved-replies-wrapper">
                                    <button type="button" class="saved-replies-button js-saved-replies-toggle"
                                        title="Saved Replies" data-toggle="tooltip">
                                        <i class="glyphicon glyphicon-comment"></i>
                                    </button>

                                    <div class="saved-replies-dropdown js-saved-replies-dropdown">
                                        @if ($saved_reply_categories->count())
                                            @foreach ($saved_reply_categories as $category)
                                                <div class="saved-replies-category-block">
                                                    <button type="button" class="saved-replies-category-toggle">
                                                        {{ $category->name }}
                                                        <span class="caret"></span>
                                                    </button>

                                                    <div class="saved-replies-category-items">

                                                        {{-- Standalone saved reply --}}
                                                        @if (!$category->children->count() && !empty($category->reply))
                                                            <button type="button" class="saved-replies-item"
                                                                data-id="{{ $category->id }}"
                                                                data-reply="{{ e($category->reply) }}">
                                                                Use this reply
                                                            </button>

                                                            {{-- Category with child replies --}}
                                                        @elseif ($category->children && $category->children->count())
                                                            @foreach ($category->children as $reply)
                                                                <button type="button" class="saved-replies-item"
                                                                    data-id="{{ $reply->id }}"
                                                                    data-reply="{{ e($reply->reply) }}">
                                                                    {{ $reply->name }}
                                                                </button>
                                                            @endforeach

                                                            {{-- Empty category --}}
                                                        @else
                                                            <div class="saved-replies-empty-small">
                                                                No replies yet.
                                                            </div>
                                                        @endif

                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="saved-replies-empty">
                                                No saved replies yet.
                                            </div>
                                        @endif

                                        <div class="saved-replies-save-new">
                                            <button type="button" class="saved-replies-save-this js-saved-replies-save-this">
                                                Save This Reply...
                                            </button>
                                        </div>
                                    </div>
                                </div>
