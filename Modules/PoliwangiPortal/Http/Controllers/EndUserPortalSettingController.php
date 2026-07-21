<?php

namespace Modules\PoliwangiPortal\Http\Controllers;

use Illuminate\Http\Request;
use App\Mailbox;
use Illuminate\Routing\Controller;
use Modules\PoliwangiCustomField\Models\CustomField;
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

        // Ambil custom fields milik mailbox ini hanya jika modul aktif
        $customFields = collect();
        if (\Module::isActive('poliwangicustomfield')) {
            $customFields = CustomField::where('mailbox_id', $mailbox->id)
                ->orderBy('id', 'asc')
                ->get();
        }

        return view('poliwangiportal::end_user_portal.setting', compact(
            'mailbox',
            'setting',
            'customFields'
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

        $request->validate([
            'submit_ticket_title' => 'required|string|max:255',
            'footer' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'custom_fields.*' => 'integer',
            'portal_url' => 'nullable|string|max:255',
        ]);

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

        /*
     * Ambil custom field yang dicentang dari form.
     * Isinya harus berupa ID, contoh: [1, 2, 5]
     */
        $selectedCustomFields = $request->get('custom_fields', []);

        /*
     * Amankan data:
     * hanya simpan custom field yang benar-benar milik mailbox ini.
     */
        $validCustomFieldIds = CustomField::where('mailbox_id', $mailbox->id)
            ->whereIn('id', $selectedCustomFields)
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();

        // Update hanya bagian End-User Portal atas
        $setting->update([
            'portal_url' => $request->portal_url ?: $setting->portal_url,

            'submit_ticket_title' => $request->submit_ticket_title,

            // Simpan ID custom field, bukan nama field
            'custom_fields' => json_encode($validCustomFieldIds),

            // Toggle bagian atas
            'subject_field' => $request->has('subject_field'),
            'consent_checkbox' => $request->has('consent_checkbox'),
            'show_ticket_numbers' => $request->has('show_ticket_numbers'),

            // Footer
            'footer' => $request->footer ?: '© {%year%} {%mailbox.name%}',

            // Checkbox bagian atas
            'only_existing_customers' => $request->has('only_existing_customers'),
        ]);

        return redirect()
            ->route('PoliwangiPortal.end_user_portal.setting', $mailbox->id)
            ->with('success', 'End-User Portal settings saved successfully.');
    }


}
