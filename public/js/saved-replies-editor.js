document.addEventListener('DOMContentLoaded', function () {
    console.log('Saved Replies editor aktif');

    function closest(element, selector) {
        while (element && element !== document) {
            if (element.matches && element.matches(selector)) {
                return element;
            }

            element = element.parentNode;
        }

        return null;
    }

    function syncSavedReplyTextarea(editorBox) {
    if (!editorBox) {
        return;
    }

    var editor = editorBox.querySelector('.rich-editor-editable') ||
        editorBox.querySelector('.saved-reply-editable');

    var textarea = editorBox.querySelector('.rich-editor-textarea') ||
        editorBox.querySelector('.saved-reply-hidden-textarea');

    if (editor && textarea) {
        textarea.value = editor.innerHTML;
    }
}

    function focusEditor(editor) {
        if (!editor) {
            return;
        }

        editor.focus();

        if (window.getSelection && document.createRange) {
            var range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);

            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        }
    }

    function insertHtmlToEditor(editorBox, editor, html) {
        focusEditor(editor);

        if (document.queryCommandSupported && document.queryCommandSupported('insertHTML')) {
            document.execCommand('insertHTML', false, html);
        } else {
            editor.innerHTML += html;
        }

        syncSavedReplyTextarea(editorBox);
    }

    function insertTextToEditor(editorBox, editor, text) {
        focusEditor(editor);

        if (document.queryCommandSupported && document.queryCommandSupported('insertText')) {
            document.execCommand('insertText', false, text);
        } else {
            document.execCommand('insertHTML', false, text);
        }

        syncSavedReplyTextarea(editorBox);
    }

    var currentImageEditorBox = null;
    var currentImageEditor = null;

    function openImagePicker(editorBox, editor) {
    currentImageEditorBox = editorBox;
    currentImageEditor = editor;

    var fileInput = document.getElementById('saved-reply-image-file');
    var urlInput = document.getElementById('saved-reply-image-url');

    if (fileInput) {
        fileInput.value = '';
    }

    if (urlInput) {
        urlInput.value = '';
    }

    document.body.classList.add('rich-editor-image-modal-open');

    $('#savedReplyImageModal').modal({
        backdrop: true,
        keyboard: true,
        show: true
    });
}
$('#savedReplyImageModal').on('hidden.bs.modal', function () {
    document.body.classList.remove('rich-editor-image-modal-open');
});

    document.addEventListener('click', function (e) {
    if (!e.target.matches('#saved-reply-insert-image-btn')) {
        return;
    }

        e.preventDefault();

        var fileInput = document.getElementById('saved-reply-image-file');
        var urlInput = document.getElementById('saved-reply-image-url');

        var file = fileInput && fileInput.files.length > 0 ? fileInput.files[0] : null;
        var imageUrl = urlInput ? urlInput.value.trim() : '';

        if (!currentImageEditorBox || !currentImageEditor) {
            alert('Editor tidak ditemukan.');
            return;
        }

        if (file) {
            var reader = new FileReader();

            reader.onload = function (event) {
                var imageHtml =
                    '<p><img src="' + event.target.result + '" style="max-width:100%; height:auto;"></p>';

                insertHtmlToEditor(currentImageEditorBox, currentImageEditor, imageHtml);

                $('#savedReplyImageModal').modal('hide');
            };

            reader.readAsDataURL(file);
            return;
        }

        if (imageUrl) {
            var imageHtml =
                '<p><img src="' + imageUrl + '" style="max-width:100%; height:auto;"></p>';

            insertHtmlToEditor(currentImageEditorBox, currentImageEditor, imageHtml);

            $('#savedReplyImageModal').modal('hide');
            return;
        }

        alert('Pilih file gambar atau isi Image URL terlebih dahulu.');
    });

    function openAttachmentPicker(editorBox, editor) {
        var input = document.createElement('input');

        input.type = 'file';
        input.style.display = 'none';

        input.addEventListener('change', function () {
            var file = input.files[0];

            if (!file) {
                return;
            }

            var attachmentHtml =
                '<p><span class="glyphicon glyphicon-paperclip"></span> Attachment: ' +
                file.name +
                '</p>';

            insertHtmlToEditor(editorBox, editor, attachmentHtml);
        });

        document.body.appendChild(input);
        input.click();

        setTimeout(function () {
            if (input.parentNode) {
                input.parentNode.removeChild(input);
            }
        }, 1000);
    }

    document.addEventListener('mousedown', function (e) {
        var toolbarButton = closest(e.target, '.saved-reply-toolbar .toolbar-btn');

        if (toolbarButton) {
            e.preventDefault();
        }
    });

    document.addEventListener('click', function (e) {
        var button = closest(e.target, '.saved-reply-toolbar .toolbar-btn');

        if (!button) {
            return;
        }

        e.preventDefault();

        var command = button.getAttribute('data-command');
        var editorBox = closest(button, '.saved-reply-editor');
        var editor = editorBox ? editorBox.querySelector('.saved-reply-editable') : null;

        if (!editor) {
            alert('Editor tidak ditemukan. Pastikan ada class saved-reply-editor.');
            return;
        }

        focusEditor(editor);

        if (command === 'attachment') {
            openAttachmentPicker(editorBox, editor);
            return;
        }

        if (command === 'insertImage') {
            openImagePicker(editorBox, editor);
            return;
        }

        if (command === 'createLink') {
            var url = prompt('Masukkan URL link:');

            if (url) {
                document.execCommand('createLink', false, url);
                syncSavedReplyTextarea(editorBox);
            }

            return;
        }

        if (command === 'insertTable') {
            var tableHtml =
                '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">' +
                '<tr><td>Kolom 1</td><td>Kolom 2</td></tr>' +
                '<tr><td>Data 1</td><td>Data 2</td></tr>' +
                '</table><br>';

            insertHtmlToEditor(editorBox, editor, tableHtml);
            return;
        }

        if (command === 'code') {
            document.execCommand('formatBlock', false, 'pre');
            syncSavedReplyTextarea(editorBox);
            return;
        }

        document.execCommand(command, false, null);

        if (
            command === 'bold' ||
            command === 'italic' ||
            command === 'underline' ||
            command === 'insertUnorderedList'
        ) {
            button.classList.toggle('is-active');
        }

        syncSavedReplyTextarea(editorBox);
    });

    /*
     * INSERT VARIABLE
     * Support untuk select dengan optgroup:
     *
     * Mailbox
     * - Email
     * - Name
     *
     * Conversation
     * - Number
     *
     * Customer
     * - Full Name
     * - First Name
     * - Last Name
     * - Email Address
     */
    document.addEventListener('change', function (e) {
        if (!e.target.matches('.insert-variable-select')) {
            return;
        }

        var select = e.target;
        var variable = select.value;

        if (!variable) {
            return;
        }

        var editorBox = closest(select, '.saved-reply-editor');
        var editor = editorBox ? editorBox.querySelector('.saved-reply-editable') : null;

        if (!editor) {
            alert('Editor tidak ditemukan. Pastikan ada class saved-reply-editor.');
            select.value = '';
            return;
        }

        insertTextToEditor(editorBox, editor, variable);

        select.value = '';
    });

    document.addEventListener('input', function (e) {
        if (!e.target.matches('.saved-reply-editable')) {
            return;
        }

        var editorBox = closest(e.target, '.saved-reply-editor');
        syncSavedReplyTextarea(editorBox);
    });

    document.addEventListener('keyup', function (e) {
        if (!e.target.matches('.saved-reply-editable')) {
            return;
        }

        var editorBox = closest(e.target, '.saved-reply-editor');
        syncSavedReplyTextarea(editorBox);
    });

    document.addEventListener('paste', function (e) {
        if (!e.target.matches('.saved-reply-editable')) {
            return;
        }

        var editorBox = closest(e.target, '.saved-reply-editor');

        setTimeout(function () {
            syncSavedReplyTextarea(editorBox);
        }, 100);
    });

    document.addEventListener('blur', function (e) {
        if (!e.target.matches('.saved-reply-editable')) {
            return;
        }

        var editorBox = closest(e.target, '.saved-reply-editor');
        syncSavedReplyTextarea(editorBox);
    }, true);

    document.addEventListener('submit', function (e) {
        if (!e.target.matches('.saved-reply-form')) {
            return;
        }

        var editors = e.target.querySelectorAll('.saved-reply-editor');

        editors.forEach(function (editorBox) {
            syncSavedReplyTextarea(editorBox);
        });
    });
});


document.addEventListener('DOMContentLoaded', function () {
    console.log('Toolbar dropdown fix aktif');

    function updateColorIcon(target, command, value) {
    var dropdown = target.closest('.sr-color-dropdown');
    var icon = dropdown ? dropdown.querySelector('.sr-color-icon') : null;

    if (!icon) {
        return;
    }

    var currentForeColor = icon.getAttribute('data-fore-color') || '#1f6fa8';
    var currentBackColor = icon.getAttribute('data-back-color') || '#fff200';

    if (command === 'foreColor') {
        currentForeColor = value || '#1f6fa8';
    }

    if (command === 'backColor') {
        currentBackColor = value === 'transparent' ? 'transparent' : value;
    }

    icon.setAttribute('data-fore-color', currentForeColor);
    icon.setAttribute('data-back-color', currentBackColor);

    icon.style.color = currentForeColor;

    if (currentBackColor === 'transparent') {
        icon.style.background = 'transparent';
    } else {
        icon.style.background =
            'linear-gradient(to bottom, transparent 55%, ' + currentBackColor + ' 55%)';
    }
}

    function getEditorBox(element) {
        return element.closest('.rich-editor') || element.closest('.saved-reply-editor');
    }

    function getEditor(editorBox) {
        if (!editorBox) {
            return null;
        }

        return editorBox.querySelector('.rich-editor-editable') ||
            editorBox.querySelector('.saved-reply-editable');
    }

    function getTextarea(editorBox) {
        if (!editorBox) {
            return null;
        }

        return editorBox.querySelector('.rich-editor-textarea') ||
            editorBox.querySelector('.saved-reply-hidden-textarea');
    }

    function syncEditor(editorBox) {
        var editor = getEditor(editorBox);
        var textarea = getTextarea(editorBox);

        if (editor && textarea) {
            textarea.value = editor.innerHTML;
        }
    }

    function closeDropdowns() {
        document.querySelectorAll('.sr-toolbar-dropdown.open').forEach(function (dropdown) {
            dropdown.classList.remove('open');
        });
    }

    function focusEditor(editor) {
        if (editor) {
            editor.focus();
        }
    }

    function insertTable(editorBox, editor, rows, cols) {
        var html = '<table>';

        for (var r = 1; r <= rows; r++) {
            html += '<tr>';

            for (var c = 1; c <= cols; c++) {
                html += '<td>&nbsp;</td>';
            }

            html += '</tr>';
        }

        html += '</table><br>';

        focusEditor(editor);
        document.execCommand('insertHTML', false, html);
        syncEditor(editorBox);
    }

    /*
     * Buka / tutup dropdown toolbar
     */
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('.sr-toolbar-toggle');

        if (toggle) {
            e.preventDefault();
            e.stopPropagation();

            var dropdown = toggle.closest('.sr-toolbar-dropdown');

            document.querySelectorAll('.sr-toolbar-dropdown.open').forEach(function (item) {
                if (item !== dropdown) {
                    item.classList.remove('open');
                }
            });

            dropdown.classList.toggle('open');
            return;
        }

        if (!e.target.closest('.sr-toolbar-dropdown')) {
            closeDropdowns();
        }
    });

    /*
     * Jangan sampai klik dropdown menghilangkan fokus editor
     */
    document.addEventListener('mousedown', function (e) {
        if (e.target.closest('.sr-toolbar-dropdown')) {
            e.preventDefault();
        }
    });

    /*
     * List dropdown
     */
    document.addEventListener('click', function (e) {
        var item = e.target.closest('.sr-menu-btn');

        if (!item) {
            return;
        }

        e.preventDefault();

        var command = item.getAttribute('data-command');
        var editorBox = getEditorBox(item);
        var editor = getEditor(editorBox);

        if (!editor || !command) {
            return;
        }

        focusEditor(editor);
        document.execCommand(command, false, null);
        syncEditor(editorBox);
        closeDropdowns();
    });

    /*
     * Style dropdown
     */
    document.addEventListener('click', function (e) {
        var item = e.target.closest('.sr-style-item');

        if (!item) {
            return;
        }

        e.preventDefault();

        var command = item.getAttribute('data-command');
        var value = item.getAttribute('data-value');

        var editorBox = getEditorBox(item);
        var editor = getEditor(editorBox);

        if (!editor) {
            return;
        }

        focusEditor(editor);

        if (command === 'formatBlock') {
            document.execCommand('formatBlock', false, value);
        }

        syncEditor(editorBox);
        closeDropdowns();
    });

    /*
     * Color dropdown
     */
    document.addEventListener('click', function (e) {
        var colorCell = e.target.closest('.sr-color-cell');
        var colorAction = e.target.closest('.sr-color-action');

        if (!colorCell && !colorAction) {
            return;
        }

        e.preventDefault();

        var target = colorCell || colorAction;
        var palette = target.closest('.sr-color-palette');

        var command = target.getAttribute('data-command') ||
            (palette ? palette.getAttribute('data-command') : null);

        var value = target.getAttribute('data-value');

        var editorBox = getEditorBox(target);
        var editor = getEditor(editorBox);

        if (!editor || !command) {
            return;
        }

        focusEditor(editor);

        if (value === 'transparent') {
            document.execCommand(command, false, 'transparent');
            updateColorIcon(target, command, 'transparent');
        } else if (value === '') {
            document.execCommand(command, false, '#000000');
            updateColorIcon(target, command, '#1f6fa8');
        } else {
            document.execCommand(command, false, value);
            updateColorIcon(target, command, value);
        }

        syncEditor(editorBox);
        closeDropdowns();
    });

    /*
     * Table hover
     */
    document.addEventListener('mouseover', function (e) {
        var cell = e.target.closest('.sr-table-cell');

        if (!cell) {
            return;
        }

        var rows = parseInt(cell.getAttribute('data-rows'), 10);
        var cols = parseInt(cell.getAttribute('data-cols'), 10);
        var menu = cell.closest('.sr-table-menu');

        if (!menu) {
            return;
        }

        menu.querySelectorAll('.sr-table-cell').forEach(function (item) {
            var itemRows = parseInt(item.getAttribute('data-rows'), 10);
            var itemCols = parseInt(item.getAttribute('data-cols'), 10);

            if (itemRows <= rows && itemCols <= cols) {
                item.classList.add('hovered');
            } else {
                item.classList.remove('hovered');
            }
        });

        var size = menu.querySelector('.sr-table-size');

        if (size) {
            size.textContent = cols + ' x ' + rows;
        }
    });

    /*
     * Table insert
     */
    document.addEventListener('click', function (e) {
        var cell = e.target.closest('.sr-table-cell');

        if (!cell) {
            return;
        }

        e.preventDefault();

        var rows = parseInt(cell.getAttribute('data-rows'), 10);
        var cols = parseInt(cell.getAttribute('data-cols'), 10);

        var editorBox = getEditorBox(cell);
        var editor = getEditor(editorBox);

        if (!editor) {
            return;
        }

        insertTable(editorBox, editor, rows, cols);
        closeDropdowns();
    });
});
