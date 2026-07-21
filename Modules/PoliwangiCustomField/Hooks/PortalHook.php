<?php

namespace Modules\PoliwangiCustomField\Hooks;

use Modules\PoliwangiCustomField\Models\CustomField;
use Modules\PoliwangiCustomField\Models\CustomFieldValue;

class PortalHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        /*
         * Hook 1: Menampilkan field di form submit ticket
         */
        \Eventy::addAction('portal.ticket.form_bottom', function ($mailbox, $setting) {
            if (!$mailbox || !$setting) return;
            
            // Ambil field yang dicentang di setting
            $selectedCustomFieldIds = self::getSelectedCustomFieldIds($setting);
            
            if (empty($selectedCustomFieldIds)) return;
            
            $fields = CustomField::where('mailbox_id', $mailbox->id)
                ->whereIn('id', $selectedCustomFieldIds)
                ->orderBy('id', 'asc')
                ->get();
                
            if ($fields->count() > 0) {
                echo view('poliwangicustomfield::end_user_portal.submit_ticket_fields', [
                    'fields' => $fields
                ])->render();
            }
        }, 20, 2);

        /*
         * Hook 2: Menambahkan aturan validasi sebelum tiket disimpan
         */
        \Eventy::addFilter('portal.ticket.validation_rules', function ($rules, $request, $setting, $mailbox) {
            $selectedCustomFieldIds = self::getSelectedCustomFieldIds($setting);
            
            if (empty($selectedCustomFieldIds) || !$request->has('custom_fields')) {
                return $rules;
            }

            $validCustomFields = CustomField::where('mailbox_id', $mailbox->id)
                ->whereIn('id', $selectedCustomFieldIds)
                ->get();

            foreach ($validCustomFields as $field) {
                $rules = array_merge($rules, $field->getValidationRules('custom_fields.'));
            }

            return $rules;
        }, 20, 4);

        /*
         * Hook 3: Menambahkan pesan error validasi
         */
        \Eventy::addFilter('portal.ticket.validation_messages', function ($messages, $request, $setting, $mailbox) {
            $selectedCustomFieldIds = self::getSelectedCustomFieldIds($setting);
            
            if (empty($selectedCustomFieldIds) || !$request->has('custom_fields')) {
                return $messages;
            }

            $validCustomFields = CustomField::where('mailbox_id', $mailbox->id)
                ->whereIn('id', $selectedCustomFieldIds)
                ->get();

            foreach ($validCustomFields as $field) {
                $messages = array_merge($messages, $field->getValidationMessages('custom_fields.'));
            }

            return $messages;
        }, 20, 4);

        /*
         * Hook 4: Menyimpan data setelah tiket dibuat
         */
        \Eventy::addAction('portal.ticket.submitted', function ($conversation, $request, $setting, $mailbox) {
            if (!$request->has('custom_fields')) return;

            $inputCustomFields = $request->input('custom_fields', []);
            $selectedCustomFieldIds = self::getSelectedCustomFieldIds($setting);

            $validCustomFieldIds = CustomField::where('mailbox_id', $mailbox->id)
                ->whereIn('id', $selectedCustomFieldIds)
                ->pluck('id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->toArray();

            if (is_array($inputCustomFields)) {
                foreach ($inputCustomFields as $customFieldId => $value) {
                    $customFieldId = (int) $customFieldId;

                    if (!in_array($customFieldId, $validCustomFieldIds)) {
                        continue;
                    }

                    if ($value === null || $value === '' || $value === []) {
                        continue;
                    }

                    CustomFieldValue::create([
                        'conversation_id' => $conversation->id,
                        'custom_field_id' => $customFieldId,
                        'value' => is_array($value) ? json_encode($value) : $value,
                    ]);
                }
            }
        }, 20, 4);
    }

    private static function getSelectedCustomFieldIds($setting)
    {
        if (!$setting || empty($setting->custom_fields)) {
            return [];
        }

        $selectedCustomFieldIds = $setting->custom_fields;

        if (!is_array($selectedCustomFieldIds)) {
            $selectedCustomFieldIds = json_decode($selectedCustomFieldIds, true) ?: [];
        }

        return array_map('intval', $selectedCustomFieldIds);
    }
}
