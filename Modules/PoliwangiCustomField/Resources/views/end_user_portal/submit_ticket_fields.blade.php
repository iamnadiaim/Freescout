@foreach ($fields as $field)
    <div class="form-group {{ $field->type_field == 'textarea' ? 'full' : '' }}">
        <label class="form-label">
            {{ $field->nama_field }}
            @if ($field->required)
                <span style="color: #d93025;">*</span>
            @endif
        </label>

        @if ($field->type_field == 'textarea')
            <textarea name="custom_fields[{{ $field->id }}]"
                      class="form-control"
                      placeholder="{{ $field->nama_field }}"
                      {{ $field->required ? 'required' : '' }}>{{ old('custom_fields.' . $field->id) ?? '' }}</textarea>
        
        @elseif ($field->type_field == 'number')
            <input type="number"
                   name="custom_fields[{{ $field->id }}]"
                   class="form-control"
                   value="{{ old('custom_fields.' . $field->id) ?? '' }}"
                   placeholder="{{ $field->nama_field }}"
                   {{ $field->required ? 'required' : '' }}>

        @elseif ($field->type_field == 'date')
            <input type="date"
                   name="custom_fields[{{ $field->id }}]"
                   class="form-control"
                   value="{{ old('custom_fields.' . $field->id) ?? '' }}"
                   {{ $field->required ? 'required' : '' }}>

        @elseif ($field->type_field == 'dropdown')
            @php
                $options = is_array($field->options)
                    ? $field->options
                    : json_decode($field->options, true);

                $options = $options ?: [];
            @endphp

            <select name="custom_fields[{{ $field->id }}]"
                    class="form-control"
                    {{ $field->required ? 'required' : '' }}>
                <option value="">Pilih {{ $field->nama_field }}</option>

                @foreach ($options as $option)
                    <option value="{{ $option }}"
                            {{ old('custom_fields.' . $field->id) == $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            </select>

        @elseif ($field->type_field == 'multiselect')
            @php
                $options = is_array($field->options)
                    ? $field->options
                    : json_decode($field->options, true);

                $options = $options ?: [];
                $oldValues = old('custom_fields.' . $field->id, []);
            @endphp

            <select name="custom_fields[{{ $field->id }}][]"
                    class="form-control custom-field-multiselect"
                    multiple
                    data-placeholder="Pilih {{ $field->nama_field }}"
                    {{ $field->required ? 'required' : '' }}>
                @foreach ($options as $option)
                    <option value="{{ $option }}"
                            {{ in_array($option, $oldValues) ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            </select>

        @else
            <input type="text"
                   name="custom_fields[{{ $field->id }}]"
                   class="form-control"
                   value="{{ old('custom_fields.' . $field->id) ?? '' }}"
                   placeholder="{{ $field->nama_field }}"
                   {{ $field->required ? 'required' : '' }}>
        @endif
    </div>
@endforeach
