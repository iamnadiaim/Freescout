@extends('poliwangiportal::layouts.app')

@section('title_full', 'End-User Portal - ' . $mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('module_content')

    {{-- PAGE TITLE --}}
    <div class="section-heading">
        End-User Portal
    </div>

    {{-- FLASH MESSAGE --}}
    @include('partials/flash_messages')

    {{-- VALIDATION ERROR --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="margin-bottom: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- CONTENT WRAPPER --}}
    <div class="row-container">
        <div class="row">
            <div class="col-xs-12">

                <form action="{{ route('PoliwangiPortal.end_user_portal.update', $mailbox->id) }}" method="POST"
                    class="form-horizontal end-user-portal-form">

                    {{ csrf_field() }}

                    {{-- URL --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            URL
                        </label>

                        <div class="col-sm-8">
                            <a href="{{ $setting->portal_url }}" target="_blank" class="btn btn-primary">
                                <i class="glyphicon glyphicon-new-window"></i>
                                End-User Portal
                            </a>

                            <input type="hidden" name="portal_url" value="{{ $setting->portal_url }}">

                            <p class="help-block portal-url-text">
                                {{ $setting->portal_url }}
                            </p>
                        </div>
                    </div>

                    {{-- SUBMIT TICKET TITLE --}}
                    <div class="form-group submit-ticket-title-group">
                        <label class="col-sm-2 control-label">
                            Submit a Ticket
                        </label>

                        <div class="col-sm-8">
                            <input type="text" name="submit_ticket_title" class="form-control"
                                value="{{ old('submit_ticket_title', $setting->submit_ticket_title) }}">
                        </div>
                    </div>

                    {{-- CUSTOM FIELDS (Injected via Hook) --}}
                    @if (class_exists('\Eventy'))
                        {!! \Eventy::action('portal.setting.form_middle', $mailbox, $setting) !!}
                    @endif

                    {{-- SUBJECT FIELD --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Subject Field
                        </label>

                        <div class="col-sm-8">
                            <label class="switch">
                                <input type="checkbox" name="subject_field" value="1"
                                    {{ old('subject_field', $setting->subject_field) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    {{-- CONSENT CHECKBOX --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Consent Checkbox
                        </label>

                        <div class="col-sm-8">
                            <label class="switch">
                                <input type="checkbox" name="consent_checkbox" value="1"
                                    {{ old('consent_checkbox', $setting->consent_checkbox) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    {{-- SHOW TICKET NUMBERS --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Show Ticket Numbers
                        </label>

                        <div class="col-sm-8">
                            <label class="switch">
                                <input type="checkbox" name="show_ticket_numbers" value="1"
                                    {{ old('show_ticket_numbers', $setting->show_ticket_numbers) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    {{-- FOOTER --}}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Footer
                        </label>

                        <div class="col-sm-8">
                            <textarea name="footer" class="form-control end-user-footer" rows="8">{{ old('footer', $setting->footer) }}</textarea>
                        </div>
                    </div>

                    {{-- ONLY EXISTING CUSTOMERS --}}
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-8">
                            <label class="checkbox-label">
                                <input type="checkbox" name="only_existing_customers" value="1"
                                    {{ old('only_existing_customers', $setting->only_existing_customers) ? 'checked' : '' }}>

                                Only existing customers having tickets are allowed to login to End-User Portal
                            </label>
                        </div>
                    </div>

                    {{-- SAVE BUTTON --}}
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-8">
                            <button type="submit" class="btn btn-primary">
                                Save
                            </button>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>
    </div>


@endsection

@section('stylesheets')
    @parent

    <link href="{{ asset('css/end_portal_user.css') }}" rel="stylesheet">
@endsection
@section('javascript')
    @parent

    var widgetAutoSaveTimer = null;

    function getWidgetAutoSaveData() {
    return {
    _token: '{{ csrf_token() }}',
    widget_disabled: $('input[name="widget_disabled"]').is(':checked') ? 1 : 0,
    widget_main_color: $('#widget_main_color').val(),
    widget_position: $('select[name="widget_position"]').val(),
    widget_language: $('select[name="widget_language"]').val()
    };
    }

    function runWidgetAutoSave() {
    $('#widget_auto_save_status').text('Saving...');

    $.ajax({
    url: '{{ route('PoliwangiPortal.end_user_portal.widget_auto_save', $mailbox->id) }}',
    type: 'POST',
    data: getWidgetAutoSaveData(),
    success: function (response) {
    if (response.success) {
    $('#widget_code_box').val(response.widget_code);

    $('#widget_auto_save_status').text('Saved');

    setTimeout(function () {
    $('#widget_auto_save_status').text('');
    }, 1500);
    } else {
    $('#widget_auto_save_status').text('Failed to save');
    }
    },
    error: function () {
    $('#widget_auto_save_status').text('Failed to save');
    }
    });
    }

    function delayWidgetAutoSave() {
    clearTimeout(widgetAutoSaveTimer);

    widgetAutoSaveTimer = setTimeout(function () {
    runWidgetAutoSave();
    }, 600);
    }

    $('#widget_main_color_picker').on('input', function () {
    $('#widget_main_color').val($(this).val());
    delayWidgetAutoSave();
    });

    $('#widget_main_color').on('input', function () {
    $('#widget_main_color_picker').val($(this).val());
    delayWidgetAutoSave();
    });

    $(document).on('change', '.widget-auto-save', function () {
    delayWidgetAutoSave();
    });

    window.previewWidgetCode = function () {
    alert('Preview widget belum dibuat. Gunakan Open in New Window untuk sementara.');
    };

@endsection
