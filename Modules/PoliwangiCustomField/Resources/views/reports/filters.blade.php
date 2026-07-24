@if (isset($customFields) && $customFields->count())
    @foreach ($customFields as $customField)
        @php
            $selectedValue = isset($selectedCustomFields[$customField->id])
                ? $selectedCustomFields[$customField->id]
                : '';
        @endphp

        @if (in_array($customField->type_field, ['dropdown', 'select', 'radio', 'tags', 'multiselect']))
            <label>{{ $customField->nama_field }}</label>

            <select name="custom_fields[{{ $customField->id }}]" class="custom-select"
                onchange="this.form.submit()">
                <option value="">All</option>

                @foreach ($customField->options_array as $option)
                    @php
                        $optionLabel = is_array($option)
                            ? $option['label'] ?? ($option['value'] ?? '')
                            : $option;

                        $optionValue = is_array($option)
                            ? $option['value'] ?? ($option['label'] ?? '')
                            : $option;
                    @endphp

                    <option value="{{ $optionValue }}"
                        {{ (string) $selectedValue === (string) $optionValue ? 'selected' : '' }}>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>
        @else
            <label>{{ $customField->nama_field }}</label>

            <input type="text" name="custom_fields[{{ $customField->id }}]"
                value="{{ $selectedValue }}" class="custom-field-input" placeholder="All"
                onchange="this.form.submit()">
        @endif
    @endforeach
@endif
