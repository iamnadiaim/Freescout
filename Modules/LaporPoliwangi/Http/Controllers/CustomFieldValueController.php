<?php

namespace Modules\LaporPoliwangi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\LaporPoliwangi\Models\CustomField;
use Modules\LaporPoliwangi\Models\CustomFieldValue;

class CustomFieldValueController extends Controller
{
    /**
     * Simpan / update custom field values
     */
    public function store(Request $request)
    {
        $conversation_id = $request->conversation_id;
        $mailbox_id = $request->mailbox_id;

        $customFieldsInput = $request->get('custom_fields', []);

        if (!$conversation_id || !$mailbox_id) {
            return redirect()->back()->with('error', 'Conversation atau mailbox tidak ditemukan.');
        }

        foreach ($customFieldsInput as $customFieldId => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $value = trim((string) $value);

            CustomFieldValue::updateOrCreate(
                [
                    'custom_field_id' => $customFieldId,
                    'conversation_id' => $conversation_id,
                ],
                [
                    'value' => $value,
                ]
            );
        }

        return redirect()->back()->with('success', 'Custom field berhasil disimpan');
    }

    /**
     * Ambil semua custom field + value berdasarkan conversation
     */
    public function getByConversation($conversation_id)
    {
        $data = CustomFieldValue::with('customField')
            ->where('conversation_id', $conversation_id)
            ->get();

        return response()->json($data);
    }

    /**
     * Ambil field + value (untuk edit form)
     */
    public function getForm($conversation_id, $mailbox_id)
    {
        $fields = CustomField::where('mailbox_id', $mailbox_id)->get();

        $values = CustomFieldValue::where('conversation_id', $conversation_id)
            ->pluck('value', 'custom_field_id');

        return view('custom_fields.form', compact('fields', 'values', 'conversation_id', 'mailbox_id'));
    }
}
