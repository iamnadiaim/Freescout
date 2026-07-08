(function (window, document) {
    'use strict';

    function runWhenJqueryReady(callback) {
        if (window.jQuery) {
            callback(window.jQuery);
            return;
        }

        var tries = 0;
        var timer = window.setInterval(function () {
            tries++;

            if (window.jQuery) {
                window.clearInterval(timer);
                callback(window.jQuery);
                return;
            }

            if (tries >= 50) {
                window.clearInterval(timer);

                if (window.console) {
                    window.console.error('[NC] jQuery tidak ditemukan. notification-channels.js berhenti.');
                }
            }
        }, 100);
    }

    runWhenJqueryReady(function ($) {
        var config = window.NotificationChannelsConfig || {};

        function decodeConfig(encodedConfig) {
            if (!encodedConfig) {
                return {};
            }

            try {
                return JSON.parse(window.atob(encodedConfig));
            } catch (error) {
                return {};
            }
        }

        function showConfiguration(prefix, type) {
            prefix = String(prefix || '');
            type = String(type || '');

            var sections = $('.nc-config-section[data-config-prefix="' + prefix + '"]');

            sections.each(function () {
                var section = $(this);

                section
                    .removeClass('nc-config-visible')
                    .hide();

                section
                    .find('input, select, textarea')
                    .prop('disabled', true)
                    .prop('required', false);
            });

            if (!type) {
                return;
            }

            var activeSection = $(
                '.nc-config-section[data-config-prefix="' + prefix + '"][data-config-type="' + type + '"]'
            );

            if (!activeSection.length) {
                if (window.console) {
                    window.console.warn('[NC] Section config tidak ditemukan:', prefix, type);
                }

                return;
            }

            activeSection
                .addClass('nc-config-visible')
                .css('display', 'block');

            activeSection
                .find('input, select, textarea')
                .prop('disabled', false)
                .each(function () {
                    var field = $(this);

                    if (field.attr('data-required-for') === type) {
                        field.prop('required', true);
                    }
                });
        }

        function moveNotificationModalsToBody() {
            var modalIds = [
                '#addNotificationChannelModal',
                '#editNotificationChannelModal',
                '#notificationRulesModal'
            ];

            $.each(modalIds, function (index, modalId) {
                var modal = $(modalId);

                if (
                    modal.length &&
                    modal.parent()[0] !== document.body
                ) {
                    modal.appendTo(document.body);
                }
            });
        }

        function initializeChannelTypeSelect() {
            $(document)
                .off('change.ncChannelType', '.nc-type-select')
                .on('change.ncChannelType', '.nc-type-select', function () {
                    var select = $(this);
                    var prefix = String(select.attr('data-form-prefix') || '');
                    var type = String(select.val() || '');

                    showConfiguration(prefix, type);
                });
        }

        function initializeAddChannelModal() {
            $(document)
                .off('show.bs.modal.ncAddChannel', '#addNotificationChannelModal')
                .on('show.bs.modal.ncAddChannel', '#addNotificationChannelModal', function () {
                    showConfiguration(
                        'add',
                        String($('#add-channel-type').val() || '')
                    );
                });

            $(document)
                .off('shown.bs.modal.ncAddChannel', '#addNotificationChannelModal')
                .on('shown.bs.modal.ncAddChannel', '#addNotificationChannelModal', function () {
                    $('#add-channel-type').trigger('change');
                });

            $(document)
                .off('hidden.bs.modal.ncAddReset', '#addNotificationChannelModal')
                .on('hidden.bs.modal.ncAddReset', '#addNotificationChannelModal', function () {
                    if (config.openAddModalOnLoad) {
                        return;
                    }

                    var form = $('#addNotificationChannelForm');

                    if (
                        form.length &&
                        form.get(0)
                    ) {
                        form.get(0).reset();
                    }

                    $('#add-channel-type').val('');
                    showConfiguration('add', '');
                });
        }

        function fillEditConfigFields(form, decodedConfig) {
            form.find('.nc-edit-config-field').each(function () {
                var input = $(this);

                var fieldName = String(
                    input.attr('data-field-name') || ''
                );

                var defaultValue = input.attr('data-default-value');

                var isSecureField =
                    Number(input.attr('data-secure-field')) === 1;

                if (!fieldName) {
                    return;
                }

                if (isSecureField) {
                    input.val('');
                    return;
                }

                if (
                    decodedConfig[fieldName] !== undefined &&
                    decodedConfig[fieldName] !== null
                ) {
                    input.val(decodedConfig[fieldName]);
                    return;
                }

                if (defaultValue !== undefined) {
                    input.val(defaultValue);
                    return;
                }

                input.val('');
            });
        }

        function initializeEditChannelModal() {
            $(document)
                .off('click.ncEditChannel', '.nc-edit-button')
                .on('click.ncEditChannel', '.nc-edit-button', function () {
                    var button = $(this);
                    var form = $('#editNotificationChannelForm');

                    var channelId = String(
                        button.attr('data-id') || ''
                    );

                    var channelType = String(
                        button.attr('data-type') || ''
                    );

                    var updateUrl = String(
                        form.attr('data-update-url') || ''
                    );

                    var decodedConfig = decodeConfig(
                        button.attr('data-config') || ''
                    );

                    form.attr(
                        'action',
                        updateUrl.replace('__CHANNEL_ID__', channelId)
                    );

                    $('#edit-channel-name').val(
                        button.attr('data-name') || ''
                    );

                    $('#edit-channel-type').val(channelType);

                    $('#edit-mailbox-id').val(
                        button.attr('data-mailbox-id') || ''
                    );

                    $('#edit-is-active').prop(
                        'checked',
                        Number(button.attr('data-is-active')) === 1
                    );

                    fillEditConfigFields(form, decodedConfig);

                    showConfiguration('edit', channelType);
                });

            $(document)
                .off('shown.bs.modal.ncEditChannel', '#editNotificationChannelModal')
                .on('shown.bs.modal.ncEditChannel', '#editNotificationChannelModal', function () {
                    $('#edit-channel-type').trigger('change');
                });
        }

        function initializeDeleteChannel() {
            $(document)
                .off('submit.ncDeleteChannel', '.nc-delete-form')
                .on('submit.ncDeleteChannel', '.nc-delete-form', function (event) {
                    var channelName = String(
                        $(this).attr('data-channel-name') || ''
                    );

                    var confirmed = window.confirm(
                        'Hapus notification channel "' + channelName + '"?'
                    );

                    if (!confirmed) {
                        event.preventDefault();
                    }
                });
        }

        function initializeToggleChannel() {
            $(document)
                .off('submit.ncToggleChannel', '.nc-toggle-form')
                .on('submit.ncToggleChannel', '.nc-toggle-form', function (event) {
                    event.preventDefault();

                    var form = $(this);

                    var channelId = String(
                        form.attr('data-channel-id') || ''
                    );

                    var button = $('#channel-toggle-button-' + channelId);
                    var status = $('#channel-status-' + channelId);

                    var editButton = $(
                        '#notification-channel-row-' +
                        channelId +
                        ' .nc-edit-button'
                    );

                    var testButton = $(
                        '#notification-channel-row-' +
                        channelId +
                        ' .nc-test-button'
                    );

                    if (
                        !channelId ||
                        !button.length ||
                        button.prop('disabled')
                    ) {
                        return;
                    }

                    button.prop('disabled', true);

                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        dataType: 'json',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .done(function (response) {
                            if (
                                !response ||
                                response.success !== true
                            ) {
                                window.alert(
                                    response && response.message
                                        ? response.message
                                        : 'Status channel gagal diperbarui.'
                                );

                                return;
                            }

                            var isActive =
                                Number(response.is_active) === 1;

                            status
                                .removeClass('nc-status-active nc-status-inactive')
                                .addClass(response.status_class);

                            status
                                .find('.nc-status-text')
                                .text(isActive ? 'Aktif' : 'Tidak Aktif');

                            button
                                .removeClass('btn-default btn-success')
                                .addClass(response.button_class);

                            button
                                .find('.glyphicon')
                                .removeClass('glyphicon-pause glyphicon-play')
                                .addClass(response.button_icon);

                            button
                                .find('.nc-toggle-label')
                                .text(isActive ? 'Nonaktifkan' : 'Aktifkan');

                            editButton.attr(
                                'data-is-active',
                                isActive ? '1' : '0'
                            );

                            testButton.prop('disabled', !isActive);
                        })
                        .fail(function (xhr) {
                            var message =
                                'Status notification channel gagal diperbarui.';

                            if (
                                xhr.responseJSON &&
                                xhr.responseJSON.message
                            ) {
                                message = xhr.responseJSON.message;
                            } else if (xhr.status === 404) {
                                message = 'Channel atau route toggle tidak ditemukan.';
                            } else if (xhr.status === 405) {
                                message = 'Method route tidak sesuai.';
                            } else if (xhr.status === 419) {
                                message = 'Sesi atau CSRF token sudah berakhir. Muat ulang halaman.';
                            } else if (xhr.status === 500) {
                                message = 'Terjadi kesalahan server saat mengubah status channel.';
                            }

                            window.alert(message);

                            if (window.console) {
                                window.console.error(
                                    'Toggle notification channel gagal:',
                                    xhr.status,
                                    xhr.responseText
                                );
                            }
                        })
                        .always(function () {
                            button.prop('disabled', false);
                        });
                });
        }

        function initializePage() {
            moveNotificationModalsToBody();

            initializeChannelTypeSelect();
            initializeAddChannelModal();
            initializeEditChannelModal();
            initializeDeleteChannel();
            initializeToggleChannel();

            showConfiguration(
                'add',
                String($('#add-channel-type').val() || '')
            );

            showConfiguration(
                'edit',
                String($('#edit-channel-type').val() || '')
            );

            if (config.openAddModalOnLoad) {
                $('#add-channel-type').val(
                    String(config.oldChannelType || '')
                );

                $('#addNotificationChannelModal').modal('show');

                showConfiguration(
                    'add',
                    String(config.oldChannelType || '')
                );
            }
        }

        $(document).ready(initializePage);
    });

})(window, document);
