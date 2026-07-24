<div class="form-group">
    <label class="col-sm-2 control-label">
        Custom Fields
    </label>

    <div class="col-sm-8">

        @php
            $selectedFields = $setting->custom_fields ?? [];

            if (!is_array($selectedFields)) {
                $selectedFields = json_decode($selectedFields, true) ?: [];
            }

            $selectedFields = array_map('strval', $selectedFields);
        @endphp

        @forelse ($customFields as $field)
            <label class="checkbox-inline">
                <input type="checkbox" name="custom_fields[]" value="{{ $field->id }}"
                    {{ in_array((string) $field->id, $selectedFields) ? 'checked' : '' }}>

                {{ $field->nama_field }}
            </label>
        @empty
            <p class="form-control-static text-muted">
                Belum ada custom field untuk mailbox ini.
            </p>
        @endforelse

    </div>
</div>
