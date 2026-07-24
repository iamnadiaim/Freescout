<?php

namespace Modules\PoliwangiPortal\Http\Controllers;

use Illuminate\Http\Request;
use App\Mailbox;
use Illuminate\Routing\Controller;
use Modules\PoliwangiPortal\Models\EndUserPortalSetting;

class EndUserPortalSettingController extends Controller
{
    private function authorizeSettings(Mailbox $mailbox)
    {
        $user = auth()->user();
        if (!$user || !$user->can('updateSettings', $mailbox)) {
            abort(403, 'Unauthorized action.');
        }
    }
    /**
     * Menampilkan halaman setting End-User Portal.
     */
    public function index($mailbox_id)
    {
        // Ambil mailbox berdasarkan ID
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorizeSettings($mailbox);

        // Ambil setting jika sudah ada
        // Jika belum ada, buat data default sementara
        $setting = EndUserPortalSetting::firstOrCreate(
            [
                'mailbox_id' => $mailbox->id,
            ],
            [
                'portal_url' => url('/help/' . $mailbox->id),
                'submit_ticket_title' => 'Submit a Ticket',
                'custom_fields' => [],
                'subject_field' => false,
                'consent_checkbox' => false,
                'show_ticket_numbers' => false,
                'footer' => '© {%year%} {%mailbox.name%}',
                'only_existing_customers' => false,

            ]
        );

        return view('poliwangiportal::end_user_portal.setting', compact(
            'mailbox',
            'setting'
        ));
    }

    /**
     * Menyimpan pengaturan End-User Portal bagian atas.
     * Bagian Contact Form Widget tidak disimpan di sini,
     * karena sudah memakai autoSaveWidget().
     */
    public function update(Request $request, $mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorizeSettings($mailbox);

        $rules = [
            'submit_ticket_title' => 'required|string|max:255',
            'footer' => 'nullable|string',
            'portal_url' => 'nullable|string|max:255',
        ];

        // Allow external modules to append validation rules
        $rules = \Eventy::filter('portal.setting.validation_rules', $rules, $request, $mailbox);

        $request->validate($rules);

        $setting = EndUserPortalSetting::firstOrCreate(
            [
                'mailbox_id' => $mailbox->id,
            ],
            [
                'portal_url' => url('/help/' . $mailbox->id),
                'submit_ticket_title' => 'Submit a Ticket',
                'custom_fields' => [],
                'subject_field' => false,
                'consent_checkbox' => false,
                'show_ticket_numbers' => false,
                'footer' => '© {%year%} {%mailbox.name%}',
                'only_existing_customers' => false,

            ]
        );

        $settingData = [
            'portal_url' => $request->portal_url ?: $setting->portal_url,
            'submit_ticket_title' => $request->submit_ticket_title,
            // Toggle bagian atas
            'subject_field' => $request->has('subject_field'),
            'consent_checkbox' => $request->has('consent_checkbox'),
            'show_ticket_numbers' => $request->has('show_ticket_numbers'),
            // Footer
            'footer' => $request->footer ?: '© {%year%} {%mailbox.name%}',
            // Checkbox bagian atas
            'only_existing_customers' => $request->has('only_existing_customers'),
        ];

        // Allow external modules to modify the setting data before saving
        $settingData = \Eventy::filter('portal.setting.update_data', $settingData, $request, $mailbox);

        // Update hanya bagian End-User Portal atas
        $setting->update($settingData);

        return redirect()
            ->route('PoliwangiPortal.end_user_portal.setting', $mailbox->id)
            ->with('success', 'End-User Portal settings saved successfully.');
    }


}
