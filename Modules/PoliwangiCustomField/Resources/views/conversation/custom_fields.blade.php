                @if (isset($custom_fields) && $custom_fields->count())
                    <div class="conv-block custom-field-view-block">
                        <div class="row">
                            @foreach ($custom_fields as $field)
                                @php
                                    // Ambil value berdasarkan custom_field_id.
                                    // Kalau belum ada value, tampilkan kosong.
                                    $value = isset($custom_field_values[$field->id])
                                        ? $custom_field_values[$field->id]
                                        : '';

                                    if ($value === null) {
                                        $value = '';
                                    }

                                    $decodedValue = json_decode($value, true);

                                    $options = [];

                                    if (is_array($field->options)) {
                                        $options = $field->options;
                                    } elseif (!empty($field->options)) {
                                        $decodedOptions = json_decode($field->options, true);
                                        $options = is_array($decodedOptions) ? $decodedOptions : [];
                                    }
                                @endphp

                                <div class="custom-field-view-item col-xs-12 col-md-6" data-conversation-id="{{ $conversation->id }}"
                                    data-custom-field-id="{{ $field->id }}" style="margin-bottom: 15px;">

                                    <label class="custom-field-view-label">
                                        {{ $field->nama_field ?? '-' }}

                                        @if ($field->required)
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>

                                    @if ($field->type_field == 'textarea')
                                        <textarea class="form-control custom-field-auto-save custom-field-edit-textarea" data-old-value="{{ $value }}">{{ $value }}</textarea>
                                    @elseif ($field->type_field == 'number')
                                        <input type="number"
                                            class="form-control custom-field-auto-save custom-field-edit-input"
                                            value="{{ $value }}" data-old-value="{{ $value }}">
                                    @elseif ($field->type_field == 'date')
                                        <input type="date"
                                            class="form-control custom-field-auto-save custom-field-edit-input"
                                            value="{{ $value }}" data-old-value="{{ $value }}">
                                    @elseif ($field->type_field == 'dropdown')
                                        <select class="form-control custom-field-auto-save custom-field-edit-input"
                                            data-old-value="{{ $value }}">

                                            <option value="">- Pilih -</option>

                                            @foreach ($options as $option)
                                                <option value="{{ $option }}"
                                                    @if ($value == $option) selected @endif>
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif ($field->type_field == 'multiselect')
                                        <select multiple="multiple" id="custom-field-multiselect-{{ $field->id }}"
                                            class="custom-field-auto-save custom-field-multiselect"
                                            data-placeholder="Select one or more options"
                                            data-old-value="{{ is_array($decodedValue) ? implode('|', $decodedValue) : '' }}"
                                            style="width: 100%;">

                                            @foreach ($options as $option)
                                                <option value="{{ $option }}"
                                                    @if (is_array($decodedValue) && in_array($option, $decodedValue)) selected @endif>
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text"
                                            class="form-control custom-field-auto-save custom-field-edit-input"
                                            value="{{ $value }}" data-old-value="{{ $value }}">
                                    @endif

                                    <small class="custom-field-save-status text-help"></small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
