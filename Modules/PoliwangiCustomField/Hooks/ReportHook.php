<?php

namespace Modules\PoliwangiCustomField\Hooks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        // Render custom fields HTML in the report filter area (Generic Action)
        \Eventy::addAction('report.render_filters', function ($mailboxId, $request) {
            if (!$mailboxId || !Schema::hasTable('custom_fields')) {
                return;
            }

            $customFields = DB::table('custom_fields')
                ->where('mailbox_id', $mailboxId)
                ->orderBy('id', 'asc')
                ->get();

            if ($customFields->isEmpty()) {
                return;
            }
            
            // Format options_array for the view
            $customFields = $customFields->map(function ($field) {
                $options = [];
                if (!empty($field->options)) {
                    $decoded = json_decode($field->options, true);
                    if (is_array($decoded)) {
                        $options = $decoded;
                    }
                }
                $field->options_array = $options;
                return $field;
            });

            $selectedCustomFields = $request->get('custom_fields', []);

            echo view('poliwangicustomfield::reports.filters', [
                'customFields' => $customFields,
                'selectedCustomFields' => $selectedCustomFields
            ])->render();
        }, 20, 2);

        // Filter conversation query based on selected custom fields (Generic Filter)
        \Eventy::addFilter('report.filter_conversation_query', function ($query, $request) {
            $selectedCustomFields = $request->get('custom_fields', []);

            if (!Schema::hasTable('custom_field_values') || !is_array($selectedCustomFields) || empty($selectedCustomFields)) {
                return $query;
            }

            foreach ($selectedCustomFields as $fieldId => $fieldValue) {
                if ($fieldValue === null || $fieldValue === '') {
                    continue;
                }

                $customConversationIds = DB::table('custom_field_values')
                    ->where('custom_field_id', $fieldId)
                    ->where('value', 'like', '%' . $fieldValue . '%')
                    ->pluck('conversation_id')
                    ->toArray();

                $query->whereIn('id', $customConversationIds);
            }

            return $query;
        }, 20, 2);
    }
}
