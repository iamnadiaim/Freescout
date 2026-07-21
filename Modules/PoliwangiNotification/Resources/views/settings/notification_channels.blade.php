@php
    $notificationChannels = isset($notificationChannels) ? $notificationChannels : collect();
    $mailboxes = isset($mailboxes) ? $mailboxes : collect();

    /*
     * Get drivers from config.
     * Only drivers that have a sender are used.
     * Keys such as webhooks are ignored because they are outside drivers.
     */
    $drivers = config('notification_channels.drivers', []);

    if ($drivers instanceof \Illuminate\Support\Collection) {
        $drivers = $drivers->all();
    }

    if (!is_array($drivers)) {
        $drivers = [];
    }

    $drivers = array_filter($drivers, function ($driver) {
        return is_array($driver) && !empty($driver['sender']);
    });

    $supportedTypes = array_keys($drivers);

    /*
     * Simple helper to create a field label from the field name.
     * Example: bot_token becomes Bot Token.
     */
    $makeFieldLabel = function ($fieldName) {
        return ucwords(str_replace('_', ' ', $fieldName));
    };

    /*
     * Helper to determine the input type from field validation.
     */
    $detectInputType = function ($fieldName, $ruleString) {
        $ruleString = is_array($ruleString) ? implode('|', $ruleString) : (string) $ruleString;

        if (
            str_contains($fieldName, 'token') ||
            str_contains($fieldName, 'password') ||
            str_contains($fieldName, 'secret')
        ) {
            return 'password';
        }

        if (str_contains($ruleString, 'url')) {
            return 'url';
        }

        if (str_contains($ruleString, 'email')) {
            return 'email';
        }

        if (str_contains($ruleString, 'integer') || str_contains($ruleString, 'numeric')) {
            return 'number';
        }

        return 'text';
    };

    /*
     * Helper to get maxlength from max:xxx validation.
     */
    $detectMaxLength = function ($ruleString, $default = 255) {
        $ruleString = is_array($ruleString) ? implode('|', $ruleString) : (string) $ruleString;

        if (preg_match('/max:(\d+)/', $ruleString, $matches)) {
            return (int) $matches[1];
        }

        return $default;
    };

    /*
     * Helper to check the required rule.
     */
    $isRequiredRule = function ($ruleString) {
        $ruleString = is_array($ruleString) ? implode('|', $ruleString) : (string) $ruleString;

        return str_contains($ruleString, 'required');
    };

    /*
     * Helper to detect select options from in:a,b,c validation.
     */
    $detectOptions = function ($ruleString) {
        $ruleString = is_array($ruleString) ? implode('|', $ruleString) : (string) $ruleString;

        if (preg_match('/in:([^|]+)/', $ruleString, $matches)) {
            $values = explode(',', $matches[1]);

            return collect($values)
                ->mapWithKeys(function ($value) {
                    return [$value => ucwords(str_replace('_', ' ', $value))];
                })
                ->all();
        }

        return [];
    };

    /*
     * Helper to determine the icon from config.
     * If the config does not define an icon, use a common fallback.
     */
    $getDriverIcon = function ($type, $driver = []) {
        if (!empty($driver['icon'])) {
            return $driver['icon'];
        }

        $fallbackIcons = [
            'telegram' => 'glyphicon-send',
            'whatsapp' => 'glyphicon-phone',
            'email' => 'glyphicon-envelope',
            'webhook' => 'glyphicon-link',
        ];

        return $fallbackIcons[$type] ?? 'glyphicon-cog';
    };

    /*
     * Driver label helper.
     */
    $getDriverLabel = function ($type, $driver = []) {
        return $driver['label'] ?? ucfirst($type);
    };

    /*
     * Short channel detail helper for the table.
     * Avoid showing token/password/secret values.
     */
    $getChannelDetail = function ($type, $config = []) use ($makeFieldLabel) {
        if (!is_array($config) || empty($config)) {
            return ucfirst($type);
        }

        foreach ($config as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $lowerKey = strtolower($key);

            if (
                str_contains($lowerKey, 'token') ||
                str_contains($lowerKey, 'password') ||
                str_contains($lowerKey, 'secret')
            ) {
                continue;
            }

            return $makeFieldLabel($key) . ': ' . $value;
        }

        return ucfirst($type);
    };
    $getSenderFieldHelp = function ($driver, $fieldName) {
        if (empty($driver['sender'])) {
            return [];
        }

        $senderClass = $driver['sender'];

        if (!class_exists($senderClass)) {
            return [];
        }

        if (!method_exists($senderClass, 'fieldHelps')) {
            return [];
        }

        $helps = $senderClass::fieldHelps();

        if (!is_array($helps)) {
            return [];
        }

        return $helps[$fieldName] ?? [];
    };

    $getDriverIconHtml = function ($type, $driver = []) use ($getDriverIcon) {
        if (!empty($driver['icon_html'])) {
            return $driver['icon_html'];
        }

        $icon = $getDriverIcon($type, $driver);

        return '<i class="glyphicon ' . e($icon) . '"></i>';
    };

@endphp

<div class="nc-setting-wrapper">

    {{-- =========================================================
         ALERT
         ========================================================= --}}

    @if (session('success'))
        <div class="alert alert-success">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>

            <strong>Success!</strong>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>

            <strong>Failed!</strong>
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>

            <strong>Please review the following data:</strong>

            <ul class="nc-error-list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- =========================================================
         TOOLBAR
         ========================================================= --}}

    <div class="nc-toolbar">
        <div class="nc-toolbar-content">

            <p class="nc-toolbar-help">
                Manage notification media used to receive information about reports or conversations.
            </p>
        </div>

        <button type="button" class="btn btn-primary nc-add-btn" data-toggle="modal"
            data-target="#addNotificationChannelModal">
            <i class="glyphicon glyphicon-plus"></i>
            Add Channel
        </button>
    </div>


    {{-- =========================================================
         CHANNEL LIST
         ========================================================= --}}

    <div class="nc-list-panel">
        <div class="nc-list-heading">
            <span>Notification Channel List</span>

            <span class="nc-channel-count">
                {{ $notificationChannels->count() }} channel
            </span>
        </div>

        @if ($notificationChannels->isEmpty())
            <div class="nc-empty-state">
                <div class="nc-empty-icon">
                    <i class="glyphicon glyphicon-send"></i>
                </div>

                <h4>No notification channels yet</h4>

                <p>
                    Add a notification channel to start receiving notifications.
                </p>

                <button type="button" class="btn btn-primary" data-toggle="modal"
                    data-target="#addNotificationChannelModal">
                    <i class="glyphicon glyphicon-plus"></i>
                    Add Channel
                </button>
            </div>
        @else
            <div class="nc-table-responsive">
                <table class="table nc-table">
                    <thead>
                        <tr>
                            <th>Channel</th>
                            <th>Type</th>
                            <th>Mailbox</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($notificationChannels as $channel)
                            @php
                                $config = is_array($channel->config) ? $channel->config : [];
                                $type = strtolower($channel->type);

                                $driver = $drivers[$type] ?? [];
                                $typeLabel = $getDriverLabel($type, $driver);
                                $typeIcon = $getDriverIcon($type, $driver);
                                $channelDetail = $getChannelDetail($type, $config);

                                $safeConfig = $config;
                                foreach (['bot_token', 'api_token', 'password', 'secret', 'webhook_secret'] as $sensitiveField) {
                                    unset($safeConfig[$sensitiveField]);
                                }
                                $encodedConfig = base64_encode(json_encode($safeConfig));
                            @endphp

                            <tr id="notification-channel-row-{{ $channel->id }}">
                                <td>
                                    <div class="nc-channel-info">
                                        <div class="nc-channel-icon nc-type-{{ $type }}">
                                            {!! $getDriverIconHtml($type, $driver) !!}
                                        </div>

                                        <div class="nc-channel-content">
                                            <div class="nc-channel-name">
                                                {{ $channel->name }}
                                            </div>

                                            <div class="nc-channel-detail">
                                                {{ $channelDetail }}
                                                
                                                @if($type === 'telegram' && !empty($config['bot_token']))
                                                    <div style="margin-top: 8px;">
                                                        <button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#webhookModal-{{ $channel->id }}">
                                                            <i class="glyphicon glyphicon-cog"></i> Konfigurasi Webhook
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="nc-type-badge">
                                        {{ $typeLabel }}
                                    </span>
                                </td>

                                <td>
                                    @if ($channel->mailbox)
                                        <span class="nc-mailbox-name">
                                            {{ $channel->mailbox->name }}
                                        </span>
                                    @else
                                        <span class="nc-all-mailboxes">
                                            All Mailboxes
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <span id="channel-status-{{ $channel->id }}"
                                        class="nc-status-badge {{ $channel->is_active ? 'nc-status-active' : 'nc-status-inactive' }}">
                                        <span class="nc-status-dot"></span>

                                        <span class="nc-status-text">
                                            {{ $channel->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </span>
                                </td>

                                <td class="text-right">
                                    <div class="nc-actions">

                                        {{-- Test --}}
                                        <form method="POST"
                                            action="{{ route('notification_channels.test', $channel->id) }}"
                                            class="nc-inline-form">
                                            {{ csrf_field() }}

                                            <button type="submit" class="btn btn-info btn-sm nc-test-button"
                                                title="Test channel" {{ !$channel->is_active ? 'disabled' : '' }}>
                                                <i class="glyphicon glyphicon-send"></i>
                                                Test
                                            </button>
                                        </form>

                                        {{-- Toggle --}}
                                        <form method="POST"
                                            action="{{ route('notification_channels.toggle_active', $channel->id) }}"
                                            class="nc-inline-form nc-toggle-form"
                                            data-channel-id="{{ $channel->id }}">
                                            {{ csrf_field() }}

                                            <button type="submit" id="channel-toggle-button-{{ $channel->id }}"
                                                class="btn btn-sm {{ $channel->is_active ? 'btn-default' : 'btn-success' }}">
                                                <i
                                                    class="glyphicon {{ $channel->is_active ? 'glyphicon-pause' : 'glyphicon-play' }}"></i>

                                                <span class="nc-toggle-label">
                                                    {{ $channel->is_active ? 'Deactivate' : 'Activate' }}
                                                </span>
                                            </button>
                                        </form>

                                        {{-- Edit --}}
                                        <button type="button" class="btn btn-warning btn-sm nc-edit-button"
                                            data-toggle="modal" data-target="#editNotificationChannelModal"
                                            data-id="{{ $channel->id }}" data-name="{{ $channel->name }}"
                                            data-type="{{ $type }}"
                                            data-mailbox-id="{{ $channel->mailbox_id }}"
                                            data-is-active="{{ $channel->is_active ? 1 : 0 }}"
                                            data-config="{{ $encodedConfig }}">
                                            <i class="glyphicon glyphicon-pencil"></i>
                                            Edit
                                        </button>

                                        {{-- Delete --}}
                                        <form method="POST"
                                            action="{{ route('notification_channels.destroy', $channel->id) }}"
                                            class="nc-inline-form nc-delete-form"
                                            data-channel-name="{{ $channel->name }}">
                                            {{ csrf_field() }}
                                            {{ method_field('DELETE') }}

                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="glyphicon glyphicon-trash"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>


@section('stylesheets')
    @parent
    <link href="{{ asset('css/notification-channels.css') }}" rel="stylesheet">
@endsection

@section('body_bottom')
    @parent

    {{-- =========================================================
         ADD NOTIFICATION CHANNEL MODAL
         ========================================================= --}}

    <div class="modal fade nc-modal" id="addNotificationChannelModal" tabindex="-1" role="dialog"
        aria-labelledby="addNotificationChannelModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <form method="POST" action="{{ route('notification_channels.store') }}" id="addNotificationChannelForm"
                    autocomplete="off">
                    {{ csrf_field() }}

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                        <h4 class="modal-title" id="addNotificationChannelModalLabel">
                            <i class="glyphicon glyphicon-plus"></i>
                            Add Notification Channel
                        </h4>
                    </div>

                    <div class="modal-body">

                        @if ($errors->any() && old('_method') !== 'PUT')
                            <div class="alert alert-danger">
                                <strong>The data could not be saved.</strong>

                                <ul class="nc-error-list">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="add-channel-name">
                                Channel Name
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="name" id="add-channel-name" class="form-control"
                                value="{{ old('name') }}" maxlength="150" required>
                        </div>

                        <div class="form-group">
                            <label for="add-channel-type">
                                Type Channel
                                <span class="text-danger">*</span>
                            </label>

                            <select name="type" id="add-channel-type" class="form-control nc-type-select"
                                data-form-prefix="add" required>
                                <option value="">
                                    -- Select Channel Type --
                                </option>

                                @foreach ($drivers as $driverKey => $driver)
                                    <option value="{{ $driverKey }}"
                                        {{ old('type') === $driverKey ? 'selected' : '' }}>
                                        {{ $getDriverLabel($driverKey, $driver) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="add-mailbox-id">
                                Mailbox
                            </label>

                            <select name="mailbox_id" id="add-mailbox-id" class="form-control">
                                <option value="">
                                    All Mailboxes
                                </option>

                                @foreach ($mailboxes as $mailbox)
                                    <option value="{{ $mailbox->id }}"
                                        {{ (string) old('mailbox_id') === (string) $mailbox->id ? 'selected' : '' }}>
                                        {{ $mailbox->name }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="help-block">
                                Select a specific mailbox or leave it for all mailboxes.
                            </p>
                        </div>


                        {{-- =====================================================
                             ADD DYNAMIC DRIVER CONFIGURATION
                             ===================================================== --}}

                        @foreach ($drivers as $driverKey => $driver)
                            @php
                                $driverRules = $driver['rules'] ?? [];
                                $driverFields = $driver['fields'] ?? [];
                            @endphp

                            <div class="nc-config-section" data-config-type="{{ $driverKey }}"
                                data-config-prefix="add">
                                <div class="nc-config-title">
                                    {!! $getDriverIconHtml($driverKey, $driver) !!}
                                    Configuration {{ $getDriverLabel($driverKey, $driver) }}
                                </div>

                                @foreach ($driverRules as $fieldName => $ruleString)
                                    @php
                                        $fieldConfig = $driverFields[$fieldName] ?? [];

                                        /*
                                         * Get field help from the sender.
                                         * Example:
                                         * TelegramSender::fieldHelps()
                                         * WhatsAppSender::fieldHelps()
                                         */
                                        $senderHelp = $getSenderFieldHelp($driver, $fieldName);

                                        $fieldId = 'add-' . str_replace('_', '-', $fieldName);

                                        $fieldLabel =
                                            $senderHelp['label'] ??
                                            ($fieldConfig['label'] ?? $makeFieldLabel($fieldName));

                                        $fieldType = $fieldConfig['type'] ?? $detectInputType($fieldName, $ruleString);

                                        $fieldRequired = array_key_exists('required', $fieldConfig)
                                            ? (bool) $fieldConfig['required']
                                            : $isRequiredRule($ruleString);

                                        $fieldMaxLength = $fieldConfig['maxlength'] ?? $detectMaxLength($ruleString);

                                        $fieldPlaceholder = $fieldConfig['placeholder'] ?? '';

                                        $fieldHelp = $senderHelp['help'] ?? ($fieldConfig['help'] ?? '');

                                        $fieldHelpLink = $senderHelp['link'] ?? '';

                                        $fieldHelpLinkLabel = $senderHelp['link_label'] ?? 'Open guide';

                                        $fieldHelpExample = $senderHelp['example'] ?? '';

                                        $fieldDefault = $fieldConfig['default'] ?? '';

                                        $fieldOptions = $fieldConfig['options'] ?? $detectOptions($ruleString);

                                        $fieldSecure = array_key_exists('secure', $fieldConfig)
                                            ? (bool) $fieldConfig['secure']
                                            : str_contains($fieldName, 'token') ||
                                                str_contains($fieldName, 'password') ||
                                                str_contains($fieldName, 'secret');
                                    @endphp

                                    <div class="form-group">
                                        <label for="{{ $fieldId }}">
                                            {{ $fieldLabel }}

                                            @if ($fieldRequired)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if (!empty($fieldOptions))
                                            <select name="{{ $fieldName }}" id="{{ $fieldId }}"
                                                class="form-control"
                                                data-required-for="{{ $fieldRequired ? $driverKey : '' }}">
                                                @foreach ($fieldOptions as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}"
                                                        {{ old($fieldName, $fieldDefault) == $optionValue ? 'selected' : '' }}>
                                                        {{ $optionLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="{{ $fieldType }}" name="{{ $fieldName }}"
                                                id="{{ $fieldId }}" class="form-control"
                                                value="{{ old($fieldName, $fieldDefault) }}"
                                                maxlength="{{ $fieldMaxLength }}" placeholder="{{ $fieldPlaceholder }}"
                                                autocomplete="{{ $fieldSecure ? 'new-password' : 'off' }}"
                                                data-required-for="{{ $fieldRequired ? $driverKey : '' }}">
                                        @endif

                                        @if (!empty($fieldHelp) || !empty($fieldHelpLink) || !empty($fieldHelpExample))
                                            <p class="help-block">
                                                @if (!empty($fieldHelp))
                                                    {{ $fieldHelp }}
                                                @endif

                                                @if (!empty($fieldHelpLink))
                                                    <br>
                                                    <a href="{{ $fieldHelpLink }}" target="_blank"
                                                        rel="noopener noreferrer">
                                                        {{ $fieldHelpLinkLabel }}
                                                    </a>
                                                @endif

                                                @if (!empty($fieldHelpExample))
                                                    <br>
                                                    <code>{{ $fieldHelpExample }}</code>
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach


                        <div class="nc-switch-row">
                            <label class="nc-switch">
                                <input type="checkbox" name="is_active" value="1"
                                    {{ old('is_active', 1) ? 'checked' : '' }}>

                                <span class="nc-slider"></span>
                            </label>

                            <span class="nc-switch-text">
                                Activekan channel setelah disimpan
                            </span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i class="glyphicon glyphicon-floppy-disk"></i>
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- =========================================================
         EDIT NOTIFICATION CHANNEL MODAL
         ========================================================= --}}

    <div class="modal fade nc-modal" id="editNotificationChannelModal" tabindex="-1" role="dialog"
        aria-labelledby="editNotificationChannelModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <form method="POST" action="" id="editNotificationChannelForm"
                    data-update-url="{{ route('notification_channels.update', ['id' => '__CHANNEL_ID__']) }}"
                    autocomplete="off">
                    {{ csrf_field() }}
                    {{ method_field('PUT') }}

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                        <h4 class="modal-title" id="editNotificationChannelModalLabel">
                            <i class="glyphicon glyphicon-pencil"></i>
                            Edit Notification Channel
                        </h4>
                    </div>

                    <div class="modal-body">

                        <div id="editNotificationChannelErrors" class="alert alert-danger" style="display: none;">
                            <strong>The data could not be saved.</strong>
                            <ul class="nc-error-list"></ul>
                        </div>

                        <div class="form-group">
                            <label for="edit-channel-name">
                                Channel Name
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="name" id="edit-channel-name" class="form-control"
                                maxlength="150" required>
                        </div>

                        <div class="form-group">
                            <label for="edit-channel-type">
                                Type Channel
                                <span class="text-danger">*</span>
                            </label>

                            <select name="type" id="edit-channel-type" class="form-control nc-type-select"
                                data-form-prefix="edit" required>
                                <option value="">
                                    -- Select Channel Type --
                                </option>

                                @foreach ($drivers as $driverKey => $driver)
                                    <option value="{{ $driverKey }}">
                                        {{ $getDriverLabel($driverKey, $driver) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit-mailbox-id">
                                Mailbox
                            </label>

                            <select name="mailbox_id" id="edit-mailbox-id" class="form-control">
                                <option value="">
                                    All Mailboxes
                                </option>

                                @foreach ($mailboxes as $mailbox)
                                    <option value="{{ $mailbox->id }}">
                                        {{ $mailbox->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                        {{-- =====================================================
                             EDIT DYNAMIC DRIVER CONFIGURATION
                             ===================================================== --}}

                        @foreach ($drivers as $driverKey => $driver)
                            @php
                                $driverRules = $driver['rules'] ?? [];
                                $driverFields = $driver['fields'] ?? [];
                            @endphp

                            <div class="nc-config-section" data-config-type="{{ $driverKey }}"
                                data-config-prefix="edit">
                                <div class="nc-config-title">
                                    <i class="glyphicon {{ $getDriverIcon($driverKey, $driver) }}"></i>
                                    Configuration {{ $getDriverLabel($driverKey, $driver) }}
                                </div>

                                @foreach ($driverRules as $fieldName => $ruleString)
                                    @php
                                        $fieldConfig = $driverFields[$fieldName] ?? [];

                                        /*
                                         * Get field help from the sender.
                                         * Example:
                                         * TelegramSender::fieldHelps()
                                         * WhatsAppSender::fieldHelps()
                                         */
                                        $senderHelp = $getSenderFieldHelp($driver, $fieldName);

                                        $fieldId = 'edit-' . str_replace('_', '-', $fieldName);

                                        $fieldLabel =
                                            $senderHelp['label'] ??
                                            ($fieldConfig['label'] ?? $makeFieldLabel($fieldName));

                                        $fieldType = $fieldConfig['type'] ?? $detectInputType($fieldName, $ruleString);

                                        $fieldRequired = array_key_exists('required', $fieldConfig)
                                            ? (bool) $fieldConfig['required']
                                            : $isRequiredRule($ruleString);

                                        $fieldMaxLength = $fieldConfig['maxlength'] ?? $detectMaxLength($ruleString);

                                        $fieldPlaceholder = $fieldConfig['placeholder'] ?? '';

                                        $fieldHelp = $senderHelp['help'] ?? ($fieldConfig['help'] ?? '');

                                        $fieldHelpLink = $senderHelp['link'] ?? '';

                                        $fieldHelpLinkLabel = $senderHelp['link_label'] ?? 'Open guide';

                                        $fieldHelpExample = $senderHelp['example'] ?? '';

                                        $fieldDefault = $fieldConfig['default'] ?? '';

                                        $fieldOptions = $fieldConfig['options'] ?? $detectOptions($ruleString);

                                        $fieldSecure = array_key_exists('secure', $fieldConfig)
                                            ? (bool) $fieldConfig['secure']
                                            : str_contains($fieldName, 'token') ||
                                                str_contains($fieldName, 'password') ||
                                                str_contains($fieldName, 'secret');

                                        /*
                                         * In edit mode, secure fields are not required to be re-entered.
                                         * If left blank, the controller should keep the existing value.
                                         */
                                        $editRequired = $fieldSecure ? false : $fieldRequired;
                                    @endphp

                                    <div class="form-group">
                                        <label for="{{ $fieldId }}">
                                            {{ $fieldLabel }}

                                            @if ($editRequired)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if (!empty($fieldOptions))
                                            <select name="{{ $fieldName }}" id="{{ $fieldId }}"
                                                class="form-control nc-edit-config-field"
                                                data-field-name="{{ $fieldName }}"
                                                data-default-value="{{ $fieldDefault }}"
                                                data-required-for="{{ $editRequired ? $driverKey : '' }}">
                                                @foreach ($fieldOptions as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}">
                                                        {{ $optionLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="{{ $fieldType }}" name="{{ $fieldName }}"
                                                id="{{ $fieldId }}" class="form-control nc-edit-config-field"
                                                data-field-name="{{ $fieldName }}"
                                                data-default-value="{{ $fieldDefault }}"
                                                data-secure-field="{{ $fieldSecure ? 1 : 0 }}"
                                                maxlength="{{ $fieldMaxLength }}"
                                                placeholder="{{ $fieldSecure ? 'Leave blank to keep existing data' : $fieldPlaceholder }}"
                                                autocomplete="{{ $fieldSecure ? 'new-password' : 'off' }}"
                                                data-required-for="{{ $editRequired ? $driverKey : '' }}">
                                        @endif

                                        @if ($fieldSecure)
                                            <p class="help-block">
                                                Existing data is not displayed for security reasons.
                                            </p>
                                        @elseif (!empty($fieldHelp) || !empty($fieldHelpLink) || !empty($fieldHelpExample))
                                            <p class="help-block">
                                                @if (!empty($fieldHelp))
                                                    {{ $fieldHelp }}
                                                @endif

                                                @if (!empty($fieldHelpLink))
                                                    <br>
                                                    <a href="{{ $fieldHelpLink }}" target="_blank"
                                                        rel="noopener noreferrer">
                                                        {{ $fieldHelpLinkLabel }}
                                                    </a>
                                                @endif

                                                @if (!empty($fieldHelpExample))
                                                    <br>
                                                    <code>{{ $fieldHelpExample }}</code>
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach


                        <div class="nc-switch-row">
                            <label class="nc-switch">
                                <input type="checkbox" name="is_active" id="edit-is-active" value="1">

                                <span class="nc-slider"></span>
                            </label>

                            <span class="nc-switch-text">
                                Channel is active
                            </span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i class="glyphicon glyphicon-floppy-disk"></i>
                            Save Changes
                        </button>
                    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    @foreach ($notificationChannels as $channel)
        @php
            $config = is_array($channel->config) ? $channel->config : [];
            $type = strtolower($channel->type);
        @endphp
        @if($type === 'telegram' && !empty($config['bot_token']))
            @php
                $expectedToken = substr(hash('sha256', $config['bot_token']), 0, 32);
                $webhookUrl = route('notification_channels.webhook', ['type' => 'telegram']);
            @endphp
            <div class="modal fade" id="webhookModal-{{ $channel->id }}" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="modal-title">Konfigurasi Telegram</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Webhook URL</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{ $webhookUrl }}" id="webhook-url-{{ $channel->id }}" readonly>
                                    <span class="input-group-btn">
                                        <button class="btn btn-default nc-copy-btn" type="button" data-nc-target="#webhook-url-{{ $channel->id }}">Copy</button>
                                    </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Secret Token</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{ $expectedToken }}" id="webhook-secret-{{ $channel->id }}" readonly>
                                    <span class="input-group-btn">
                                        <button class="btn btn-default nc-copy-btn" type="button" data-nc-target="#webhook-secret-{{ $channel->id }}">Copy</button>
                                    </span>
                                </div>
                                <p class="help-block text-danger" style="margin-top: 10px;">Gunakan Secret Token ini saat mengatur webhook Telegram.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach


@endsection
@section('javascripts')
    @parent

    @if (file_exists(public_path('js/notification-channels.js')))
        <script {!! \Helper::cspNonceAttr() !!}
            src="{{ asset('js/notification-channels.js') }}?v={{ filemtime(public_path('js/notification-channels.js')) }}">
        </script>
    @endif
@endsection
