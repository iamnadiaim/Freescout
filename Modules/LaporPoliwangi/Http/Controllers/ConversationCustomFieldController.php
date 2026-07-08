<?php

namespace Modules\LaporPoliwangi\Http\Controllers;

use App\Conversation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LaporPoliwangi\Models\CustomField;
use Modules\LaporPoliwangi\Models\CustomFieldValue;

class ConversationCustomFieldController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->user();

        $conversation = Conversation::find($request->conversation_id);
        $customField = CustomField::find($request->custom_field_id);

        if (!$conversation) {
            return response()->json([
                'status' => 'error',
                'msg'    => 'Conversation not found.',
            ], 404);
        }

        if (!$customField) {
            return response()->json([
                'status' => 'error',
                'msg'    => 'Custom field not found.',
            ], 404);
        }

        if ((int) $customField->mailbox_id !== (int) $conversation->mailbox_id) {
            return response()->json([
                'status' => 'error',
                'msg'    => 'Custom field does not belong to this mailbox.',
            ], 422);
        }

        if (!$user || !$user->can('update', $conversation)) {
            return response()->json([
                'status' => 'error',
                'msg'    => __('Not enough permissions'),
            ], 403);
        }

        $value = $request->get('value');

        if (is_array($value)) {
            $value = json_encode($value);
        }

        $customFieldValue = CustomFieldValue::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'custom_field_id' => $customField->id,
            ],
            [
                'value' => $value,
            ]
        );

        return response()->json([
            'status'   => 'success',
            'msg'      => 'Custom field updated successfully.',
            'value_id' => $customFieldValue->id,
        ]);
    }
}
