<?php

namespace Modules\PoliwangiCustomField\Models;

use App\Mailbox;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    protected $table = 'custom_fields';

    protected $fillable = [
        'nama_field',
        'type_field',
        'options',
        'show_in_conversation_list',
        'required',
        'mailbox_id',
    ];

    protected $casts = [
        'options' => 'array',
        'show_in_conversation_list' => 'boolean',
        'required' => 'boolean',
    ];

    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class, 'mailbox_id');
    }

    public function values()
    {
        return $this->hasMany(CustomFieldValue::class, 'custom_field_id');
    }
    public function getValidationRules($prefix = 'custom_fields.')
    {
        $rules = [];
        $fieldKey = $prefix . $this->id;
        $validationRules = [];

        if ($this->required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($this->type_field == 'number') {
            $rules[] = 'numeric';
        } elseif ($this->type_field == 'date') {
            $rules[] = 'date';
        } elseif ($this->type_field == 'dropdown') {
            $values = array_values((array) $this->options);
            $rules[] = \Illuminate\Validation\Rule::in($values);
        } elseif ($this->type_field == 'multiselect') {
            $rules[] = 'array';
            $values = array_values((array) $this->options);
            $validationRules[$fieldKey . '.*'] = \Illuminate\Validation\Rule::in($values);
        }

        $validationRules[$fieldKey] = $rules;

        return $validationRules;
    }

    public function getValidationMessages($prefix = 'custom_fields.')
    {
        $fieldKey = $prefix . $this->id;
        $name = $this->nama_field;
        
        return [
            $fieldKey . '.required' => "Field '{$name}' wajib diisi.",
            $fieldKey . '.numeric' => "Field '{$name}' harus berupa angka.",
            $fieldKey . '.date' => "Field '{$name}' harus berupa tanggal yang valid.",
            $fieldKey . '.in' => "Pilihan pada field '{$name}' tidak valid.",
            $fieldKey . '.*.in' => "Pilihan pada field '{$name}' tidak valid.",
            $fieldKey . '.array' => "Field '{$name}' harus berupa pilihan.",
        ];
    }
}
