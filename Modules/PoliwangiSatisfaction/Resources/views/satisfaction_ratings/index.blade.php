@extends('layouts.app')

@section('title', 'Satisfaction Ratings')

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="container">

        {{-- HEADER --}}
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-heading satisfaction-page-title">
                    Satisfaction Ratings
                </h1>
            </div>
        </div>

        {{-- ALERT SUCCESS --}}
        @if (session('success'))
            <div class="portal-alert portal-alert-success">
                <span class="portal-alert-icon">✓</span>
                <div class="portal-alert-content">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        {{-- ALERT ERROR --}}
        @if ($errors->any())
            <div class="portal-alert portal-alert-danger">
                <span class="portal-alert-icon">!</span>
                <div class="portal-alert-content">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </div>
            </div>
        @endif

        @php
            $activeTab = request('tab', 'settings');
        @endphp

        {{-- TABS --}}
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="{{ $activeTab == 'settings' ? 'active' : '' }}">
                <a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">
                    Settings
                </a>
            </li>

            <li role="presentation" class="{{ $activeTab == 'translate' ? 'active' : '' }}">
                <a href="#translate" aria-controls="translate" role="tab" data-toggle="tab">
                    Translate
                </a>
            </li>
        </ul>

        <div class="tab-content satisfaction-tab-content">

            {{-- TAB SETTINGS --}}
            <div role="tabpanel" class="tab-pane {{ $activeTab == 'settings' ? 'active' : '' }}" id="settings">

                <form method="POST" action="{{ route('PoliwangiPortal.satisfaction_ratings.update_settings', $mailbox->id) }}"
                    class="form-horizontal satisfaction-form">

                    {{ csrf_field() }}

                    {{-- ENABLE RATINGS --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label" style="width:205px;">
                            Enable Ratings
                        </label>

                        <div class="col-sm-8 rating-toggle-area">
                            <label class="rating-switch">
                                <input type="checkbox" name="enabled" value="1"
                                    {{ old('enabled', $setting->enabled) ? 'checked' : '' }}>
                                <span class="rating-slider"></span>
                            </label>
                        </div>
                    </div>

                    {{-- ADD RATINGS --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label"style="width:205px;">
                            Add Ratings
                        </label>

                        <div class="col-sm-8 satisfaction-radio-area">
                            <div class="radio satisfaction-radio">
                                <label>
                                    <input type="radio" name="add_ratings_mode" value="all"
                                        {{ old('add_ratings_mode', $setting->add_ratings_mode) == 'all' ? 'checked' : '' }}>
                                    Add to all emails sent to customers.
                                </label>
                            </div>

                            <div class="radio satisfaction-radio">
                                <label>
                                    <input type="radio" name="add_ratings_mode" value="shortcode"
                                        {{ old('add_ratings_mode', $setting->add_ratings_mode) == 'shortcode' ? 'checked' : '' }}>
                                    Add only to emails containing the following shortcode
                                    <code>{%ratings.add%}</code>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- PLACEMENT --}}
                    <input type="hidden" name="placement" value="above">

                    {{-- RATINGS TEXT --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label"style="width:205px;">
                            Ratings Text
                        </label>

                        <div class="col-sm-8">
                            <textarea name="ratings_text" class="form-control" rows="5">{{ old('ratings_text', $setting->ratings_text) }}</textarea>

                            <p style="color: #7b8794; font-size: 13px;">
                                Teks ini akan tampil di atas pilihan rating.
                                Bisa pakai teks biasa seperti:
                                <code>Bagaimana penilaian Anda terhadap balasan ini?</code>
                            </p>
                        </div>
                    </div>

                    {{-- SAVING MODE --}}
                    <div class="form-group" style="margin-top: -15px;">
                        <label class="col-sm-2 control-label"style="width:205px; ">
                            Saving Mode
                        </label>

                        <div class="col-sm-8 satisfaction-radio-area">
                            <div class="radio">
                                <label>
                                    <input type="radio" name="saving_mode" value="immediate"
                                        {{ old('saving_mode', $setting->saving_mode) == 'immediate' ? 'checked' : '' }}>
                                    Save rating immediately after one of the rating links is clicked
                                </label>
                            </div>

                            <div class="radio">
                                <label>
                                    <input type="radio" name="saving_mode" value="after_send"
                                        {{ old('saving_mode', $setting->saving_mode) == 'after_send' ? 'checked' : '' }}>
                                    Save rating after Send button is clicked on the rating page
                                </label>
                            </div>

                        </div>
                    </div>

                    {{-- BUTTON --}}
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-8">
                            <button type="submit" class="btn btn-primary">
                                Save
                            </button>

                            <a href="{{ route('PoliwangiPortal.satisfaction_ratings.report', $mailbox->id) }}"
                                class="btn btn-default">
                                <i class="glyphicon glyphicon-stats"></i>
                                View Ratings
                            </a>
                        </div>
                    </div>
                </form>
                <form method="POST" action="{{ route('PoliwangiPortal.satisfaction_ratings.reset_settings', $mailbox->id) }}"
                    style="margin-top:15px;" onsubmit="return confirm('Reset Satisfaction Ratings ke default?')">
                    {{ csrf_field() }}

                    <button type="submit" class="btn btn-default">
                        <i class="glyphicon glyphicon-refresh"></i>
                        Reset to Default
                    </button>
                </form>
            </div>

            {{-- TAB TRANSLATE --}}
            <div role="tabpanel" class="tab-pane {{ $activeTab == 'translate' ? 'active' : '' }}" id="translate">

                <form method="POST" action="{{ route('PoliwangiPortal.satisfaction_ratings.update_translate', $mailbox->id) }}"
                    class="form-horizontal satisfaction-form">

                    {{ csrf_field() }}

                    {{-- PAGE TITLE --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Page Title
                        </label>

                        <div class="col-sm-8">
                            <input type="text" name="page_title" class="form-control"
                                value="{{ old('page_title', $setting->page_title) }}">
                        </div>
                    </div>

                    {{-- HEADER --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Header
                        </label>

                        <div class="col-sm-8">
                            <input type="text" name="header" class="form-control"
                                value="{{ old('header', $setting->header) }}">
                        </div>
                    </div>

                    {{-- GREAT --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Great
                        </label>

                        <div class="col-sm-8">
                            <input type="text" name="great_text" class="form-control"
                                value="{{ old('great_text', $setting->great_text) }}">
                        </div>
                    </div>

                    {{-- OKAY --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Okay
                        </label>

                        <div class="col-sm-8">
                            <input type="text" name="okay_text" class="form-control"
                                value="{{ old('okay_text', $setting->okay_text) }}">
                        </div>
                    </div>

                    {{-- NOT GOOD --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Not Good
                        </label>

                        <div class="col-sm-8">
                            <input type="text" name="not_good_text" class="form-control"
                                value="{{ old('not_good_text', $setting->not_good_text) }}">
                        </div>
                    </div>

                    {{-- COMMENT BOX --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Comment Box
                        </label>

                        <div class="col-sm-8">
                            <textarea name="comment_box_text" class="form-control" rows="3">{{ old('comment_box_text', $setting->comment_box_text) }}</textarea>
                        </div>
                    </div>

                    {{-- COMMENT PLACEHOLDER --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Comment Placeholder
                        </label>

                        <div class="col-sm-8">
                            <input type="text" name="comment_placeholder" class="form-control"
                                value="{{ old('comment_placeholder', $setting->comment_placeholder) }}">
                        </div>
                    </div>

                    {{-- SEND BUTTON --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Send Button
                        </label>

                        <div class="col-sm-8">
                            <input type="text" name="send_button_text" class="form-control"
                                value="{{ old('send_button_text', $setting->send_button_text) }}">
                        </div>
                    </div>

                    {{-- SEND CONFIRMATION --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Send Confirmation
                        </label>

                        <div class="col-sm-8">
                            <input type="text" name="send_confirmation_text" class="form-control"
                                value="{{ old('send_confirmation_text', $setting->send_confirmation_text) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-8">
                            <button type="submit" class="btn btn-primary">
                                Save
                            </button>
                        </div>
                    </div>
                </form>
                <form method="POST" action="{{ route('PoliwangiPortal.satisfaction_ratings.reset_translate', $mailbox->id) }}"
                    style="margin-top:15px;" onsubmit="return confirm('Reset Satisfaction Ratings ke default?')">
                    {{ csrf_field() }}

                    <button type="submit" class="btn btn-default">
                        <i class="glyphicon glyphicon-refresh"></i>
                        Reset to Default
                    </button>
                </form>
            </div>
        </div>

    </div>
@endsection
@section('stylesheets')
    @parent
    <link href="{{ asset('css/satisfaction_rating.css') }}" rel="stylesheet">
@endsection
