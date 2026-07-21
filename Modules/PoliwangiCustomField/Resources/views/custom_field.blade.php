@extends('layouts.app')

@section('title_full', __('Custom Fields') . ' - Mailbox ' . $mailbox->id)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('stylesheets')
    @parent
    <link href="{{ asset('css/customfield.css') }}" rel="stylesheet">
@endsection


{{-- DATA TYPE LABEL --}}
@php
    $typeLabels = [
        'dropdown' => 'Dropdown',
        'text' => 'Single Line',
        'textarea' => 'Multi Line',
        'number' => 'Number',
        'date' => 'Date',
        'multiselect' => 'Multiselect Dropdown',
    ];
@endphp

@section('content')
    {{-- INDEX PAGE --}}
    <div class="section-heading">
        {{ __('Custom Fields') }}

        <button type="button" class="btn btn-primary margin-left" data-toggle="modal" data-target="#customFieldCreateForm">
            {{ __('New Custom Field') }}
        </button>
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

    {{-- INDEX LIST --}}
    <div class="row-container">
        <div class="row">
            <div class="col-xs-12">

                <div class="custom-field-list">

                    @forelse ($fields as $field)

                        @php
                            $options = old('options')
                                ? explode(',', old('options'))
                                : (is_array($field->options)
                                    ? $field->options
                                    : []);

                            if (count($options) == 0) {
                                $options = [''];
                            }
                        @endphp


                        {{-- INDEX ITEM --}}
                        <div class="custom-field-card">

                            <div class="custom-field-header" data-toggle="collapse"
                                data-target="#field-{{ $field->id }}">

                                <div class="drag-handle">
                                    ☰
                                </div>

                                <div class="custom-field-title">
                                    {{ $field->nama_field ?? '' }}

                                    @if ($field->required)
                                        <span class="text-danger">*</span>
                                    @endif
                                </div>

                                <span class="badge badge-secondary">
                                    {{ $typeLabels[$field->type_field] ?? ($field->type_field ?? '') }}
                                </span>
                            </div>


                            {{-- EDIT FORM --}}
                            <div id="field-{{ $field->id }}" class="collapse">
                                <div class="custom-field-body">

                                    <form class="custom-field-form" method="POST"
                                        action="{{ route('PoliwangiPortal.custom_fields.update', [$mailbox->id, $field->id]) }}">

                                        {{ csrf_field() }}
                                        {{ method_field('PUT') }}

                                        <input type="hidden" name="options" class="options-hidden"
                                            value="{{ is_array($field->options) ? implode(', ', $field->options) : ($field->options ?? '') }}">

                                        {{-- EDIT NAME --}}
                                        <div class="form-group custom-field-row">
                                            <label>Name</label>

                                            <div class="custom-field-control">
                                                <input type="text" name="nama_field" class="form-control"
                                                    value="{{ old('nama_field', $field->nama_field ?? '' ) }}" required>
                                            </div>
                                        </div>

                                        {{-- EDIT TYPE - READ ONLY --}}
                                        <div class="form-group custom-field-row">
                                            <label>Type</label>

                                            <div class="custom-field-control">

                                                {{-- The type value is still submitted to the controller --}}
                                                <input type="hidden" name="type_field" value="{{ $field->type_field }}">

                                                {{-- Display only, cannot be edited --}}
                                                <select class="form-control custom-field-type" disabled>
                                                    <option value="dropdown"
                                                        {{ $field->type_field == 'dropdown' ? 'selected' : '' }}>
                                                        Dropdown
                                                    </option>

                                                    <option value="text"
                                                        {{ $field->type_field == 'text' ? 'selected' : '' }}>
                                                        Single Line
                                                    </option>

                                                    <option value="textarea"
                                                        {{ $field->type_field == 'textarea' ? 'selected' : '' }}>
                                                        Multi Line
                                                    </option>

                                                    <option value="number"
                                                        {{ $field->type_field == 'number' ? 'selected' : '' }}>
                                                        Number
                                                    </option>

                                                    <option value="date"
                                                        {{ $field->type_field == 'date' ? 'selected' : '' }}>
                                                        Date
                                                    </option>

                                                    <option value="multiselect"
                                                        {{ $field->type_field == 'multiselect' ? 'selected' : '' }}>
                                                        Multiselect Dropdown
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- EDIT OPTIONS --}}
                                        <div class="form-group custom-field-row custom-field-options-row">
                                            <label>Options</label>

                                            <div class="custom-field-control options-wrapper">
                                                @foreach ($options as $option)
                                                    <div class="option-row">
                                                        <span class="option-drag">≡</span>

                                                        <input type="text" class="form-control option-input"
                                                            value="{{ trim($option ?? '') }}">

                                                        <button type="button" class="btn btn-default btn-option-remove">
                                                            −
                                                        </button>

                                                        @if ($loop->last)
                                                            <button type="button" class="btn btn-default btn-option-add">
                                                                +
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endforeach

                                                <small class="text-muted option-help">
                                                    Fill in options for Dropdown and Multiselect Dropdown.
                                                </small>
                                            </div>
                                        </div>

                                        {{-- EDIT SHOW IN CONVERSATION LIST --}}
                                        <div class="form-group custom-field-row">
                                            <label>Show In Conv. List</label>

                                            <div class="custom-field-control">
                                                <label class="switch">
                                                    <input type="checkbox" name="show_in_conversation_list" value="1"
                                                        {{ old('show_in_conversation_list', $field->show_in_conversation_list) ? 'checked' : '' }}>

                                                    <span class="slider"></span>
                                                </label>
                                            </div>
                                        </div>

                                        {{-- EDIT REQUIRED --}}
                                        <div class="form-group custom-field-row">
                                            <label>Required</label>

                                            <div class="custom-field-control">
                                                <label class="switch">
                                                    <input type="checkbox" name="required" value="1"
                                                        {{ old('required', $field->required) ? 'checked' : '' }}>

                                                    <span class="slider"></span>
                                                </label>
                                            </div>
                                        </div>

                                        {{-- EDIT ACTIONS --}}
                                        <div class="custom-field-actions">
                                            <button type="submit" class="btn btn-primary">
                                                Save Field
                                            </button>

                                            <button type="submit" form="delete-field-{{ $field->id }}"
                                                class="btn btn-link text-danger"
                                                onclick="return confirm('Are you sure you want to delete this custom field?')">
                                                Delete
                                            </button>
                                        </div>

                                    </form>

                                    {{-- DELETE FORM --}}
                                    <form id="delete-field-{{ $field->id }}" method="POST"
                                        action="{{ route('PoliwangiPortal.custom_fields.destroy', [$mailbox->id, $field->id]) }}"
                                        style="display: none;">

                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}

                                    </form>

                                </div>
                            </div>

                        </div>

                    @empty

                        {{-- EMPTY INDEX --}}
                        <div class="custom-field-item">
                            {{ __('No custom fields found.') }}
                        </div>

                    @endforelse

                </div>

            </div>
        </div>
    </div>
@endsection

@section('body_bottom')
    @parent
    {{-- CREATE FORM --}}
    <div class="modal fade" id="customFieldCreateForm" tabindex="-1" role="dialog"
        aria-labelledby="customFieldCreateFormLabel">
        <div class="modal-dialog custom-field-create" role="document">
            <div class="modal-content">

                <form class="custom-field-form" method="POST"
                    action="{{ route('PoliwangiPortal.custom_fields.store', $mailbox->id) }}">

                    {{ csrf_field() }}

                    <input type="hidden" name="options" class="options-hidden" value="">

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                        <h4 class="modal-title" id="customFieldCreateFormLabel">
                            {{ __('New Custom Field') }}
                        </h4>
                    </div>

                    <div class="modal-body">

                        {{-- CREATE NAME --}}
                        <div class="form-group custom-field-row">
                            <label>Name</label>

                            <div class="custom-field-control">
                                <input type="text" name="nama_field" class="form-control"
                                    value="{{ old('nama_field') ?? '' }}" required>
                            </div>
                        </div>

                        {{-- CREATE TYPE --}}
                        <div class="form-group custom-field-row">
                            <label>Type</label>

                            <div class="custom-field-control">
                                <select name="type_field" class="form-control custom-field-type" required>
                                    <option value="dropdown"
                                        {{ old('type_field', 'dropdown') == 'dropdown' ? 'selected' : '' }}>
                                        Dropdown
                                    </option>

                                    <option value="text"
                                        {{ old('type_field', 'dropdown') == 'text' ? 'selected' : '' }}>
                                        Single Line
                                    </option>

                                    <option value="textarea"
                                        {{ old('type_field', 'dropdown') == 'textarea' ? 'selected' : '' }}>
                                        Multi Line
                                    </option>

                                    <option value="number"
                                        {{ old('type_field', 'dropdown') == 'number' ? 'selected' : '' }}>
                                        Number
                                    </option>

                                    <option value="date"
                                        {{ old('type_field', 'dropdown') == 'date' ? 'selected' : '' }}>
                                        Date
                                    </option>

                                    <option value="multiselect"
                                        {{ old('type_field', 'dropdown') == 'multiselect' ? 'selected' : '' }}>
                                        Multiselect Dropdown
                                    </option>
                                </select>
                            </div>
                        </div>

                        {{-- CREATE OPTIONS --}}
                        <div class="form-group custom-field-row custom-field-options-row">
                            <label>Options</label>

                            <div class="custom-field-control options-wrapper">
                                <div class="option-row">
                                    <span class="option-drag">≡</span>

                                    <input type="text" class="form-control option-input" placeholder="One option">

                                    <button type="button" class="btn btn-default btn-option-remove">
                                        −
                                    </button>

                                    <button type="button" class="btn btn-default btn-option-add">
                                        +
                                    </button>
                                </div>

                                <small class="text-muted option-help">
                                    Fill in options for Dropdown and Multiselect Dropdown.
                                </small>
                            </div>
                        </div>

                        {{-- CREATE SHOW IN CONVERSATION LIST --}}
                        <div class="form-group custom-field-row">
                            <label>Show In<br>Conv. List</label>

                            <div class="custom-field-control">
                                <label class="switch">
                                    <input type="checkbox" name="show_in_conversation_list" value="1"
                                        {{ old('show_in_conversation_list') ? 'checked' : '' }}>

                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        {{-- CREATE REQUIRED --}}
                        <div class="form-group custom-field-row">
                            <label>Required</label>

                            <div class="custom-field-control">
                                <label class="switch">
                                    <input type="checkbox" name="required" value="1"
                                        {{ old('required') ? 'checked' : '' }}>

                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer custom-field-create-footer">
                        <button type="submit" class="btn btn-primary">
                            Save Field
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
@endsection

{{-- JAVASCRIPT --}}
@section('javascript')
    @parent

    function isOptionType(type) {
        return $.inArray(type, ['dropdown', 'tags', 'multiselect']) !== -1;
    }

    function refreshOptionVisibility(form) {
        var type = form.find('.custom-field-type').val();

        var optionRow = form.find('.custom-field-options-row');
        var wrapper = form.find('.options-wrapper');

        /*
        * OPTIONS
        * Only show options for:
        * - Dropdown
        * - Tags
        * - Multiselect Dropdown
        */
        if (isOptionType(type)) {
            optionRow.show();

            if (!wrapper.find('.option-row').length) {
                wrapper.find('.option-help').before(`
                    <div class="option-row">
                        <span class="option-drag">≡</span>

                        <input type="text" class="form-control option-input" value="">

                        <button type="button" class="btn btn-default btn-option-remove">
                            −
                        </button>

                        <button type="button" class="btn btn-default btn-option-add">
                            +
                        </button>
                    </div>
                `);
            }

            wrapper.find('.btn-option-add').remove();

            wrapper.find('.option-row:last').append(`
                <button type="button" class="btn btn-default btn-option-add">
                    +
                </button>
            `);
        } else {
            optionRow.hide();

            wrapper.find('.option-input').val('');
            form.find('.options-hidden').val('');
        }
    }

    $(document).on('change', '.custom-field-type', function () {
        var form = $(this).closest('.custom-field-form');

        refreshOptionVisibility(form);
    });

    $(document).on('click', '.btn-option-add', function () {
        var wrapper = $(this).closest('.options-wrapper');
        var form = $(this).closest('.custom-field-form');
        var type = form.find('.custom-field-type').val();

        if (!isOptionType(type)) {
            return;
        }

        wrapper.find('.btn-option-add').remove();

        var row = `
            <div class="option-row">
                <span class="option-drag">≡</span>

                <input type="text" class="form-control option-input" value="">

                <button type="button" class="btn btn-default btn-option-remove">
                    −
                </button>

                <button type="button" class="btn btn-default btn-option-add">
                    +
                </button>
            </div>
        `;

        wrapper.find('.option-help').before(row);
    });

    $(document).on('click', '.btn-option-remove', function () {
        var wrapper = $(this).closest('.options-wrapper');

        if (wrapper.find('.option-row').length > 1) {
            $(this).closest('.option-row').remove();
        } else {
            $(this).closest('.option-row').find('.option-input').val('');
        }

        wrapper.find('.btn-option-add').remove();

        wrapper.find('.option-row:last').append(`
            <button type="button" class="btn btn-default btn-option-add">
                +
            </button>
        `);
    });

    $(document).on('submit', '.custom-field-form', function () {
        var form = $(this);
        var type = form.find('.custom-field-type').val();
        var values = [];

        if (isOptionType(type)) {
            form.find('.option-input').each(function () {
                var value = $.trim($(this).val());

                if (value !== '') {
                    values.push(value);
                }
            });

            form.find('.options-hidden').val(values.join(', '));
        } else {
            form.find('.options-hidden').val('');
        }
    });

    $(document).ready(function () {
        $('.custom-field-form').each(function () {
            refreshOptionVisibility($(this));
        });
    });
@endsection
