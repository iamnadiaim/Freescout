<div class="rich-editor-toolbar saved-reply-toolbar saved-reply-conversation-toolbar">
    <button type="button" class="toolbar-btn" data-command="attachment" title="Attachment">
        <i class="glyphicon glyphicon-paperclip"></i>
    </button>

    <button type="button" class="toolbar-btn" data-command="bold" title="Bold">
        <b>B</b>
    </button>

    <button type="button" class="toolbar-btn" data-command="italic" title="Italic">
        <i>I</i>
    </button>

    <button type="button" class="toolbar-btn" data-command="underline" title="Underline">
        <u>U</u>
    </button>

    {{-- COLOR DROPDOWN --}}
    <div class="sr-toolbar-dropdown sr-color-dropdown">
        <button type="button" class="toolbar-btn sr-toolbar-toggle sr-color-toggle" title="Recent Color">
            <span class="sr-color-icon" data-fore-color="#1f6fa8" data-back-color="#fff200">A</span>
            <span class="caret"></span>
        </button>

        <div class="sr-toolbar-menu sr-color-menu">
            <div class="sr-color-title">Background Color</div>

            <button type="button" class="sr-color-action" data-command="backColor" data-value="transparent">
                Transparent
            </button>

            <div class="sr-color-palette" data-command="backColor">
                @foreach (['#000000', '#434343', '#666666', '#999999', '#b7b7b7', '#cccccc', '#d9d9d9', '#efefef', '#ff0000', '#ff9900', '#ffff00', '#00ff00', '#00ffff', '#0000ff', '#9900ff', '#ff00ff', '#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3', '#cfe2f3', '#d9d2e9', '#ead1dc', '#ea9999', '#f9cb9c', '#ffe599', '#b6d7a8', '#a2c4c9', '#9fc5e8', '#b4a7d6', '#d5a6bd', '#e06666', '#f6b26b', '#ffd966', '#93c47d', '#76a5af', '#6fa8dc', '#8e7cc3', '#c27ba0', '#cc0000', '#e69138', '#f1c232', '#6aa84f', '#45818e', '#3d85c6', '#674ea7', '#a64d79', '#990000', '#b45f06', '#bf9000', '#38761d', '#134f5c', '#0b5394', '#351c75', '#741b47', '#660000', '#783f04', '#7f6000', '#274e13', '#0c343d', '#073763', '#20124d', '#4c1130'] as $color)
                    <button type="button" class="sr-color-cell" data-value="{{ $color }}"
                        style="background: {{ $color }}"></button>
                @endforeach
            </div>

            <div class="sr-color-title sr-color-title-foreground">Foreground Color</div>

            <button type="button" class="sr-color-action" data-command="foreColor" data-value="">
                Reset to default
            </button>

            <div class="sr-color-palette" data-command="foreColor">
                @foreach (['#000000', '#434343', '#666666', '#999999', '#b7b7b7', '#cccccc', '#d9d9d9', '#efefef', '#ff0000', '#ff9900', '#ffff00', '#00ff00', '#00ffff', '#0000ff', '#9900ff', '#ff00ff', '#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3', '#cfe2f3', '#d9d2e9', '#ead1dc', '#ea9999', '#f9cb9c', '#ffe599', '#b6d7a8', '#a2c4c9', '#9fc5e8', '#b4a7d6', '#d5a6bd', '#e06666', '#f6b26b', '#ffd966', '#93c47d', '#76a5af', '#6fa8dc', '#8e7cc3', '#c27ba0', '#cc0000', '#e69138', '#f1c232', '#6aa84f', '#45818e', '#3d85c6', '#674ea7', '#a64d79', '#990000', '#b45f06', '#bf9000', '#38761d', '#134f5c', '#0b5394', '#351c75', '#741b47', '#660000', '#783f04', '#7f6000', '#274e13', '#0c343d', '#073763', '#20124d', '#4c1130'] as $color)
                    <button type="button" class="sr-color-cell" data-value="{{ $color }}"
                        style="background: {{ $color }}"></button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- LIST DROPDOWN --}}
    <div class="sr-toolbar-dropdown">
        <button type="button" class="toolbar-btn sr-toolbar-toggle" title="List">
            <i class="glyphicon glyphicon-list"></i>
            <span class="caret"></span>
        </button>

        <div class="sr-toolbar-menu sr-list-menu">
            <button type="button" class="sr-menu-btn" data-command="insertUnorderedList" title="Unordered list">
                <span class="sr-unordered-list-icon"></span>
            </button>

            <button type="button" class="sr-menu-btn" data-command="insertOrderedList" title="Ordered list">
                <span class="sr-ordered-list-icon"></span>
            </button>
        </div>
    </div>

    <button type="button" class="toolbar-btn" data-command="removeFormat" title="Remove Formatting">
        <i class="glyphicon glyphicon-remove"></i>
    </button>

    {{-- STYLE DROPDOWN --}}
    <div class="sr-toolbar-dropdown">
        <button type="button" class="toolbar-btn sr-toolbar-toggle" title="Style">
            <i class="glyphicon glyphicon-text-size"></i>
            <span class="caret"></span>
        </button>

        <div class="sr-toolbar-menu sr-style-menu">
            <button type="button" class="sr-style-item sr-normal" data-command="formatBlock"
                data-value="p">Normal</button>
            <button type="button" class="sr-style-item sr-quote" data-command="formatBlock"
                data-value="blockquote">Quote</button>
            <button type="button" class="sr-style-item sr-code" data-command="formatBlock"
                data-value="pre">Code</button>
            <button type="button" class="sr-style-item sr-h1" data-command="formatBlock" data-value="h1">Header
                1</button>
            <button type="button" class="sr-style-item sr-h2" data-command="formatBlock" data-value="h2">Header
                2</button>
            <button type="button" class="sr-style-item sr-h3" data-command="formatBlock" data-value="h3">Header
                3</button>
            <button type="button" class="sr-style-item sr-h4" data-command="formatBlock" data-value="h4">Header
                4</button>
            <button type="button" class="sr-style-item sr-h5" data-command="formatBlock" data-value="h5">Header
                5</button>
            <button type="button" class="sr-style-item sr-h6" data-command="formatBlock" data-value="h6">Header
                6</button>
        </div>
    </div>

    <button type="button" class="toolbar-btn" data-command="createLink" title="Link">
        <i class="glyphicon glyphicon-link"></i>
    </button>

    {{-- TABLE DROPDOWN --}}
    <div class="sr-toolbar-dropdown">
        <button type="button" class="toolbar-btn sr-toolbar-toggle" title="Table">
            <i class="glyphicon glyphicon-th"></i>
            <span class="caret"></span>
        </button>

        <div class="sr-toolbar-menu sr-table-menu">
            <div class="sr-table-grid">
                @for ($row = 1; $row <= 5; $row++)
                    @for ($col = 1; $col <= 10; $col++)
                        <button type="button" class="sr-table-cell" data-rows="{{ $row }}"
                            data-cols="{{ $col }}"></button>
                    @endfor
                @endfor
            </div>

            <div class="sr-table-size">0 x 0</div>
        </div>
    </div>

    <button type="button" class="toolbar-btn" data-command="insertImage" title="Image">
        <i class="glyphicon glyphicon-picture"></i>
    </button>

    <button type="button" class="toolbar-btn" data-command="code" title="Code">
        &lt;/&gt;
    </button>

    <select class="form-control insert-variable-select">
        <option value="">Insert variable ...</option>

        <optgroup label="Mailbox">
            <option value="{%mailbox.email%}">Email</option>
            <option value="{%mailbox.name%}">Name</option>
        </optgroup>

        <optgroup label="Conversation">
            <option value="{%conversation.number%}">Number</option>
        </optgroup>

        <optgroup label="Customer">
            <option value="{%customer.fullName%}">Full Name</option>
            <option value="{%customer.firstName%}">First Name</option>
            <option value="{%customer.lastName%}">Last Name</option>
            <option value="{%customer.email%}">Email Address</option>
        </optgroup>
    </select>
</div>
