<?php

namespace Modules\PoliwangiCustomField\Hooks;

use Modules\PoliwangiCustomField\Models\CustomField;

class PortalSettingHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        /*
         * Hook 1: Menampilkan field di form setting End-User Portal
         */
        \Eventy::addAction('portal.setting.form_middle', function ($mailbox, $setting) {
            if (!$mailbox || !$setting) return;
            
            $customFields = CustomField::where('mailbox_id', $mailbox->id)
                ->orderBy('id', 'asc')
                ->get();
                
            echo view('poliwangicustomfield::end_user_portal.setting_fields', [
                'customFields' => $customFields,
                'setting' => $setting
            ])->render();
        }, 20, 2);

        /*
         * Hook 2: Menambahkan aturan validasi untuk custom fields di setting portal
         */
        \Eventy::addFilter('portal.setting.validation_rules', function ($rules, $request, $mailbox) {
            $rules['custom_fields'] = 'nullable|array';
            $rules['custom_fields.*'] = 'integer';
            
            return $rules;
        }, 20, 3);
        
        /*
         * Hook 3: Memanipulasi data setting yang akan disimpan (hanya menyimpan ID yang valid)
         */
        \Eventy::addFilter('portal.setting.update_data', function ($settingData, $request, $mailbox) {
            $selectedCustomFields = $request->get('custom_fields', []);
            
            $validCustomFieldIds = CustomField::where('mailbox_id', $mailbox->id)
                ->whereIn('id', $selectedCustomFields)
                ->pluck('id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->toArray();
                
            $settingData['custom_fields'] = json_encode($validCustomFieldIds);
            
            return $settingData;
        }, 20, 3);
    }
}
