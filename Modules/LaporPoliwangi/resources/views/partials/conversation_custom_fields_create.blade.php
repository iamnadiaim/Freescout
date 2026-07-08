@if (!empty($custom_fields) && $custom_fields->count())
    @foreach ($custom_fields as $field)
        @php
            $isRequired = $field->required ? 'required' : '';
            $options = is_array($field->options) ? $field->options : [];
            $oldValue = old('custom_fields.' . $field->id);
            $oldValues = old('custom_fields.' . $field->id, []);
            $oldValues = is_array($oldValues) ? $oldValues : [];
        @endphp

        <div class="form-group{{ $errors->has('custom_fields.' . $field->id) ? ' has-error' : '' }}">
            <label class="col-sm-2 control-label">
                {{ $field->nama_field }}

                @if ($field->required)
                    <span class="text-danger">*</span>
                @endif
            </label>

            <div class="col-sm-9">

                @if ($field->type_field == 'text')
                    <input type="text"
                        class="form-control"
                        name="custom_fields[{{ $field->id }}]"
                        value="{{ $oldValue }}"
                        {{ $isRequired }}>

                @elseif ($field->type_field == 'tags')
                    <input type="text"
                        class="form-control"
                        name="custom_fields[{{ $field->id }}]"
                        value="{{ $oldValue }}"
                        placeholder="Separate with commas"
                        {{ $isRequired }}>

                @elseif ($field->type_field == 'textarea')
                    <textarea
                        class="form-control"
                        name="custom_fields[{{ $field->id }}]"
                        rows="3"
                        {{ $isRequired }}>{{ $oldValue }}</textarea>

                @elseif ($field->type_field == 'number')
                    <input type="number"
                        class="form-control"
                        name="custom_fields[{{ $field->id }}]"
                        value="{{ $oldValue }}"
                        {{ $isRequired }}>

                @elseif ($field->type_field == 'date')
                    <input type="date"
                        class="form-control"
                        name="custom_fields[{{ $field->id }}]"
                        value="{{ $oldValue }}"
                        {{ $isRequired }}>

                @elseif ($field->type_field == 'dropdown')
                    <select class="form-control"
                        name="custom_fields[{{ $field->id }}]"
                        {{ $isRequired }}>
                        <option value="">-- Select --</option>

                        @foreach ($options as $option)
                            <option value="{{ $option }}"
                                {{ $oldValue == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>

                @elseif ($field->type_field == 'multiselect')
                    <div class="custom-field-select2-wrapper">
                        <select multiple="multiple"
                            id="custom-field-create-multiselect-{{ $field->id }}"
                            class="custom-field-multiselect"
                            name="custom_fields[{{ $field->id }}][]"
                            data-placeholder="Select one or more options"
                            style="width: 100%;"
                            {{ $isRequired }}>

                            @foreach ($options as $option)
                                <option value="{{ $option }}"
                                    {{ in_array($option, $oldValues) ? 'selected' : '' }}>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                @else
                    <input type="text"
                        class="form-control"
                        name="custom_fields[{{ $field->id }}]"
                        value="{{ $oldValue }}"
                        {{ $isRequired }}>
                @endif

                @include('partials/field_error', [
                    'field' => 'custom_fields.' . $field->id,
                ])

            </div>
        </div>
    @endforeach
@endif
