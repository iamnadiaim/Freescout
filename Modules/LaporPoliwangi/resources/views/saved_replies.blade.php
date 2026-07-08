@extends('laporpoliwangi::layouts.app')

@section('title_full', __('Saved Replies'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('module_content')
    <div class="section-heading">
        {{ __('Saved Replies') }}

        <button type="button" class="btn btn-primary margin-left" data-toggle="modal" data-target="#savedReplyCreateForm">
            {{ __('New Saved Reply') }}
        </button>
    </div>

    @include('partials/flash_messages')

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="margin-bottom: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row-container">
        <div class="row">
            <div class="col-xs-12">

                <div class="saved-reply-list">

                    @forelse ($saved_replies as $reply)
                        <div class="saved-reply-card">

                            {{-- HEADER --}}
                            <div class="saved-reply-header" data-toggle="collapse" data-target="#reply-{{ $reply->id }}">
                                <div class="drag-handle">
                                    ☰
                                </div>

                                <div class="saved-reply-title">

                                    {{ $reply->name }}

                                    @if ($reply->is_global)
                                        <span class="label label-info">Global</span>
                                    @endif
                                </div>

                                @if (is_null($reply->parent_id))
                                    <span class="badge badge-secondary">
                                        Category
                                    </span>
                                @elseif ($reply->parent)
                                    <span class="badge badge-secondary">
                                        {{ $reply->parent->name }}
                                    </span>
                                @else
                                    <span class="badge badge-secondary">
                                        No Category
                                    </span>
                                @endif
                            </div>

                            {{-- EDIT FORM --}}
                            <div id="reply-{{ $reply->id }}" class="collapse">
                                <div class="saved-reply-body">

                                    <form class="saved-reply-form" method="POST"
                                        action="{{ route('laporpoliwangi.saved_replies.update', [
                                            'id' => $mailbox->id,
                                            'reply_id' => $reply->id,
                                        ]) }}">

                                        {{ csrf_field() }}
                                        {{ method_field('PUT') }}

                                        {{-- EDIT NAME --}}
                                        <div class="form-group saved-reply-row">
                                            <label>Name</label>

                                            <div class="saved-reply-control">
                                                <input type="text" name="name" class="form-control"
                                                    value="{{ old('name', $reply->name) ?? '' }}" required>
                                            </div>
                                        </div>

                                        @php
                                            $hasChildren = $reply->children && $reply->children->count() > 0;
                                            $availableParents = $parents->where('id', '!=', $reply->id);
                                        @endphp

                                        @if (!$hasChildren && $availableParents->count() > 0)
                                            {{-- EDIT CATEGORY --}}
                                            <div class="form-group saved-reply-row">
                                                <label>Category</label>

                                                <div class="saved-reply-control">
                                                    <select name="parent_id" class="form-control">
                                                        <option value="">-- No Category --</option>

                                                        @foreach ($availableParents as $parent)
                                                            <option value="{{ $parent->id }}"
                                                                {{ old('parent_id', $reply->parent_id) == $parent->id ? 'selected' : '' }}>
                                                                {{ $parent->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- EDIT REPLY --}}
                                        <div class="form-group saved-reply-row saved-reply-row-editor">
                                            <label>Reply</label>

                                            <div class="saved-reply-control">

                                                @if ($hasChildren)
                                                    <p class="form-control-static text-muted">
                                                        The text is hidden as this saved reply contains nested saved replies
                                                    </p>

                                                    <textarea name="reply" class="rich-editor-textarea saved-reply-hidden-textarea"></textarea>
                                                @else
                                                    <div
                                                        class="rich-editor saved-reply-editor saved-reply-conversation-editor">

                                                        @include('laporpoliwangi::partials.toolbar')

                                                        <div class="rich-editor-editable saved-reply-editable saved-reply-conversation-body"
                                                            contenteditable="true">{!! old('reply', $reply->reply) ?? '' !!}</div>

                                                        <textarea name="reply" class="rich-editor-textarea saved-reply-hidden-textarea">{{ old('reply', $reply->reply) ?? '' }}</textarea>
                                                    </div>

                                                    @if ($availableParents->count() > 0)
                                                        <small class="text-muted">
                                                            Reply is required when this item is used as a saved reply.
                                                        </small>
                                                    @endif
                                                @endif

                                            </div>
                                        </div>

                                        {{-- EDIT GLOBAL --}}
                                        <div class="form-group saved-reply-row">
                                            <label>Global</label>

                                            <div class="saved-reply-control">
                                                <label class="switch">
                                                    <input type="checkbox" name="is_global" value="1"
                                                        {{ old('is_global', $reply->is_global) ? 'checked' : '' }}>
                                                    <span class="slider"></span>
                                                </label>
                                            </div>
                                        </div>

                                        {{-- EDIT ACTIONS --}}
                                        <div class="saved-reply-actions">
                                            <button type="submit" class="btn btn-primary">
                                                Save Reply
                                            </button>

                                            <button type="submit" form="delete-reply-{{ $reply->id }}"
                                                class="btn btn-link saved-reply-delete-btn"
                                                onclick="return confirm('Are you sure you want to delete this saved reply? If this is a category, all nested replies inside it will also be deleted.')">
                                                Delete
                                            </button>
                                        </div>

                                    </form>

                                    {{-- DELETE FORM --}}
                                    <form id="delete-reply-{{ $reply->id }}" method="POST"
                                        action="{{ route('laporpoliwangi.saved_replies.destroy', [
                                            'id' => $mailbox->id,
                                            'reply_id' => $reply->id,
                                        ]) }}"
                                        style="display: none;">

                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}

                                    </form>

                                </div>
                            </div>
                            @if ($reply->children && $reply->children->count() > 0)
                                <div class="saved-reply-children">
                                    @foreach ($reply->children as $child)
                                        <div class="saved-reply-card is-child">

                                            <div class="saved-reply-header" data-toggle="collapse"
                                                data-target="#reply-{{ $child->id }}">
                                                <div class="drag-handle">
                                                    ☰
                                                </div>

                                                <div class="saved-reply-title">
                                                    <i class="glyphicon glyphicon-globe"></i>

                                                    {{ $child->name }}

                                                    @if ($child->is_global)
                                                        <span class="label label-info">Global</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div id="reply-{{ $child->id }}" class="collapse">
                                                <div class="saved-reply-body">

                                                    <form class="saved-reply-form" method="POST"
                                                        action="{{ route('laporpoliwangi.saved_replies.update', [
                                                            'id' => $mailbox->id,
                                                            'reply_id' => $child->id,
                                                        ]) }}">

                                                        {{ csrf_field() }}
                                                        {{ method_field('PUT') }}

                                                        <div class="form-group saved-reply-row">
                                                            <label>Name</label>

                                                            <div class="saved-reply-control">
                                                                <input type="text" name="name" class="form-control"
                                                                    value="{{ old('name', $child->name) ?? '' }}" required>
                                                            </div>
                                                        </div>

                                                        <div class="form-group saved-reply-row">
                                                            <label>Category</label>

                                                            <div class="saved-reply-control">
                                                                <select name="parent_id" class="form-control">
                                                                    <option value="">-- No Category --</option>

                                                                    @foreach ($parents as $parent)
                                                                        @if ($parent->id != $child->id)
                                                                            <option value="{{ $parent->id }}"
                                                                                {{ old('parent_id', $child->parent_id) == $parent->id ? 'selected' : '' }}>
                                                                                {{ $parent->name }}
                                                                            </option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="form-group saved-reply-row saved-reply-row-editor">
                                                            <label>Reply</label>

                                                            <div class="saved-reply-control">
                                                                <div
                                                                    class="rich-editor saved-reply-editor saved-reply-conversation-editor">

                                                                    @include('laporpoliwangi::partials.toolbar')

                                                                    <div class="rich-editor-editable saved-reply-editable saved-reply-conversation-body"
                                                                        contenteditable="true">{!! old('reply', $child->reply) ?? '' !!}
                                                                    </div>

                                                                    <textarea name="reply" class="rich-editor-textarea saved-reply-hidden-textarea">{{ old('reply', $child->reply) ?? '' }}</textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="form-group saved-reply-row">
                                                            <label>Global</label>

                                                            <div class="saved-reply-control">
                                                                <label class="switch">
                                                                    <input type="checkbox" name="is_global"
                                                                        value="1"
                                                                        {{ old('is_global', $child->is_global) ? 'checked' : '' }}>
                                                                    <span class="slider"></span>
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <div class="saved-reply-actions">
                                                            <button type="submit" class="btn btn-primary">
                                                                Save Reply
                                                            </button>

                                                            <button type="submit"
                                                                form="delete-reply-{{ $child->id }}"
                                                                class="btn btn-link saved-reply-delete-btn"
                                                                onclick="return confirm('Are you sure you want to delete this saved reply?')">
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </form>

                                                    <form id="delete-reply-{{ $child->id }}" method="POST"
                                                        action="{{ route('laporpoliwangi.saved_replies.destroy', [
                                                            'id' => $mailbox->id,
                                                            'reply_id' => $child->id,
                                                        ]) }}"
                                                        style="display: none;">

                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                                    </form>

                                                </div>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="saved-reply-empty">
                            {{ __('No saved replies found.') }}
                        </div>
                    @endforelse

                </div>

            </div>
        </div>
    </div>
@endsection

@section('body_bottom')
    @parent

    {{-- CREATE FORM MODAL --}}
    <div class="modal fade" id="savedReplyCreateForm" tabindex="-1" role="dialog"
        aria-labelledby="savedReplyCreateFormLabel">
        <div class="modal-dialog saved-reply-create" role="document">
            <div class="modal-content">

                <form class="saved-reply-form" method="POST"
                    action="{{ route('laporpoliwangi.saved_replies.store', ['id' => $mailbox->id]) }}">
                    {{ csrf_field() }}

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                        <h4 class="modal-title" id="savedReplyCreateFormLabel">
                            {{ __('New Saved Reply') }}
                        </h4>
                    </div>

                    <div class="modal-body">

                        {{-- CREATE NAME --}}
                        <div class="form-group saved-reply-row">
                            <label>Name</label>

                            <div class="saved-reply-control">
                                <input type="text" name="name" class="form-control" value="{{ old('name') ?? '' }}"
                                    required>
                            </div>
                        </div>

                        @if ($parents->count() > 0)
                            {{-- CREATE CATEGORY --}}
                            <div class="form-group saved-reply-row">
                                <label>Category</label>

                                <div class="saved-reply-control">
                                    <select name="parent_id" class="form-control">
                                        <option value="">-- No Category --</option>

                                        @foreach ($parents as $parent)
                                            <option value="{{ $parent->id }}"
                                                {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                                {{ $parent->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        {{-- CREATE REPLY --}}
                        <div class="form-group saved-reply-row saved-reply-row-editor">
                            <label>Reply</label>

                            <div class="saved-reply-control">
                                <div class="rich-editor saved-reply-editor saved-reply-conversation-editor">

                                    @include('laporpoliwangi::partials.toolbar')

                                    <div class="rich-editor-editable saved-reply-editable saved-reply-conversation-body"
                                        contenteditable="true">{!! old('reply') ?? '' !!}</div>

                                    <textarea name="reply" class="rich-editor-textarea saved-reply-hidden-textarea">{{ old('reply') ?? '' }}</textarea>
                                </div>

                                @if ($parents->count() > 0)
                                    <small class="text-muted">
                                        Reply wajib diisi jika memilih Category.
                                    </small>
                                @endif
                            </div>
                        </div>

                        {{-- CREATE GLOBAL --}}
                        <div class="form-group saved-reply-row">
                            <label>Global</label>

                            <div class="saved-reply-control">
                                <label class="switch">
                                    <input type="checkbox" name="is_global" value="1"
                                        {{ old('is_global') ? 'checked' : '' }}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer saved-reply-create-footer">
                        <button type="submit" class="btn btn-primary">
                            Save Reply
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
    @include('laporpoliwangi::partials.image_modal')

@endsection
