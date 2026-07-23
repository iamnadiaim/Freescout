<?php

namespace Modules\PoliwangiPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Email;
use Modules\PoliwangiPortal\Models\EndUserPortalAccount;
use Modules\PoliwangiPortal\Models\EndUserPortalSetting;
use App\Mailbox;
use Modules\PoliwangiNotification\Models\NotificationChannel;
use Modules\PoliwangiSatisfaction\Models\SatisfactionRating;
use Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting;
use Modules\PoliwangiNotification\Services\Notifications\NotificationService;
use App\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EndUserPortalController extends Controller
{
    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * Membuat controller End User Portal.
     */
    public function __construct(
        NotificationService $notificationService
    ) {
        $this->notificationService = $notificationService;
    }

    // Method controller lainnya tetap diletakkan di bawah ini.



    /**
     * Mengamankan URL redirect.
     */
    private function safeRedirect($redirect)
    {
        if (!is_string($redirect)) {
            return route('PoliwangiPortal.end_user_portal.my_ticket');
        }

        if (preg_match('/^https?:\/\//i', $redirect)) {
            return route('PoliwangiPortal.end_user_portal.my_ticket');
        }

        if (strpos($redirect, '/help') !== 0) {
            return route('PoliwangiPortal.end_user_portal.my_ticket');
        }

        return $redirect;
    }

    public function selectAuth()
    {
        return view('poliwangiportal::end_user_portal.auth_select');
    }

    /**
     * Halaman utama End-User Portal.
     * Tidak memakai landing_page.blade.php.
     * Semua tampilan landing + form digabung di submit_ticket.blade.php.
     */
    public function selectMailbox()
    {
        return $this->showPortal(null);
    }

    /**
     * Menampilkan halaman portal submit ticket.
     *
     * Jika $mailbox_id ada, maka mailbox tersebut akan otomatis terpilih.
     * Jika $mailbox_id null, maka mailbox pertama menjadi default.
     */
    public function showPortal($mailbox_id = null)
    {
        $mailboxes = Mailbox::orderBy('name', 'asc')->get();

        if ($mailboxes->isEmpty()) {
            abort(404);
        }

        /*
     * Buat setting default untuk setiap mailbox jika belum ada.
     */
        foreach ($mailboxes as $mailboxItem) {
            EndUserPortalSetting::firstOrCreate(
                [
                    'mailbox_id' => $mailboxItem->id,
                ],
                [
                    'portal_url' => url('/help/' . $mailboxItem->id),
                    'submit_ticket_title' => 'Submit a Ticket',
                    'custom_fields' => [],
                    'subject_field' => false,
                    'consent_checkbox' => false,
                    'show_ticket_numbers' => false,
                    'footer' => '© {%year%} {%mailbox.name%}',
                    'only_existing_customers' => false,
                ]
            );
        }

        /*
     * Ambil semua setting mailbox.
     */
        $settingsByMailbox = EndUserPortalSetting::whereIn(
            'mailbox_id',
            $mailboxes->pluck('id')
        )
            ->get()
            ->keyBy('mailbox_id');

        /*
         * Note: Custom Fields retrieval has been delegated to PoliwangiCustomField module
         * via Hooks to enforce Loose Coupling architecture.
         */

        /*
     * Tentukan mailbox aktif.
     * /help/{id} => pakai id.
     * /help     => pakai mailbox pertama.
     */
        if ($mailbox_id) {
            $mailbox = Mailbox::findOrFail($mailbox_id);
        } else {
            $mailbox = $mailboxes->first();
        }

        $setting = $settingsByMailbox->get($mailbox->id);

        $loggedEmail = session('end_user_portal_email');
        $loggedCustomer = null;

        if (session()->has('end_user_portal_customer_id')) {
            $loggedCustomer = Customer::find(session('end_user_portal_customer_id'));
        }

        return view('poliwangiportal::end_user_portal.submit_ticket', compact(
            'mailboxes',
            'settingsByMailbox',
            'mailbox',
            'setting',
            'loggedEmail',
            'loggedCustomer'
        ));
    }

    /**
     * Pesan validasi untuk End User Portal.
     */
    private function validationMessages()
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama maksimal 255 karakter.',

            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',

            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',

            'subject.required' => 'Subjek laporan wajib diisi.',
            'subject.string' => 'Subjek laporan harus berupa teks.',
            'subject.max' => 'Subjek laporan maksimal 255 karakter.',

            'message.required' => 'Pesan laporan wajib diisi.',
            'message.string' => 'Pesan laporan harus berupa teks.',

            'consent.accepted' => 'Anda harus menyetujui ketentuan sebelum mengirim laporan.',

            'attachments.array' => 'Lampiran harus berupa daftar file.',
            'attachments.*.file' => 'Lampiran harus berupa file yang valid.',
            'attachments.*.max' => 'Ukuran setiap lampiran maksimal ' . floor(\Illuminate\Http\UploadedFile::getMaxFilesize() / 1024) . ' KB.',
            'attachments.*.mimes' => 'Format lampiran hanya boleh jpg, jpeg, png, pdf, doc, docx, xls, xlsx, txt, zip, atau rar.',

            'redirect.string' => 'Redirect tidak valid.',
            'captcha.required' => 'Jawaban keamanan (Captcha) wajib diisi.',
            'captcha.captcha' => 'Jawaban keamanan (Captcha) tidak sesuai.',
        ];
    }

    /**
     * Menyimpan laporan / ticket dari pelapor.
     */
    public function submitTicket(Request $request, $mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $setting = EndUserPortalSetting::where(
            'mailbox_id',
            $mailbox->id
        )->first();

        $isLoggedInEndUser = session()->has('end_user_portal_email')
            && session()->has('end_user_portal_customer_id');

        /*
     * Validasi data laporan.
     */
        $reportType = $request->input('report_type', 'terbuka');

        $rules = [
            'name' => $reportType === 'terbuka' ? 'required|string|max:255' : 'nullable|string|max:255',
            'email' => $reportType === 'terbuka' ? 'required|email|max:255' : 'nullable|email|max:255',
            'message' => 'required|string',

            'attachments' => 'nullable|array|max:10',
            'attachments.*' =>
            'file|max:' . floor(\Illuminate\Http\UploadedFile::getMaxFilesize() / 1024) . '|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip,rar',
        ];

        // @codeCoverageIgnoreStart
        if (!$isLoggedInEndUser && !app()->runningUnitTests() && env('APP_ENV') !== 'testing') {
            $rules['captcha'] = 'required|captcha';
        }
        // @codeCoverageIgnoreEnd

        if ($setting && $setting->subject_field) {
            $rules['subject'] = 'required|string|max:255';
        } else {
            $rules['subject'] = 'nullable|string|max:255';
        }

        if ($setting && $setting->consent_checkbox) {
            $rules['consent'] = 'accepted';
        }

        $validationMessages = $this->validationMessages();
        
        // Allow external modules (like Custom Fields) to append their validation rules
        $rules = \Eventy::filter('portal.ticket.validation_rules', $rules, $request, $setting, $mailbox);
        $validationMessages = \Eventy::filter('portal.ticket.validation_messages', $validationMessages, $request, $setting, $mailbox);

        $request->validate($rules, $validationMessages);



        /*
     * Tentukan subject ticket.
     */
        $subject = $request->filled('subject')
            ? trim((string) $request->input('subject'))
            : 'New Ticket from End-User Portal';
            
        if ($reportType === 'anonim') {
            $subject = '[ANONIM] ' . $subject;
        }

        if ($isLoggedInEndUser) {
            $emailValue = strtolower(trim((string) session('end_user_portal_email')));
            $customer = Customer::findOrFail(session('end_user_portal_customer_id'));
        } else {
            if ($reportType === 'anonim') {
                $nameValue = '';
                $emailValue = '';
                $originalEmailInput = '';
            } else {
                $nameValue = trim((string) $request->input('name'));
                $emailValue = strtolower(trim((string) $request->input('email')));
                $originalEmailInput = trim((string) $request->input('email'));
            }

            if ($originalEmailInput !== '') {
                $emailRow = Email::whereRaw(
                    'LOWER(email) = ?',
                    [$emailValue]
                )->first();

                if (!$emailRow) {
                    return redirect()
                        ->back()
                        ->withInput($request->all())
                        ->withErrors([
                            'email' => 'Email belum terdaftar. Silakan daftar terlebih dahulu atau kosongkan email untuk pelaporan anonim.',
                        ]);
                }

                $customer = $emailRow->customer;
            } else {
                /*
                 * Laporan tanpa email (Bisa Laporan Terbuka Tanpa Email atau Pelaporan Anonim murni).
                 * Email dummy dibuat dengan format Kode Pelacak.
                 */
                if ($reportType === 'anonim') {
                    $nameValue = 'Pelapor Anonim';
                    $domainDummy = '@anonim.local';
                } else {
                    if ($nameValue === '') {
                        $nameValue = 'Pelapor Tanpa Nama';
                    }
                    $domainDummy = '@terbuka.local';
                }
                
                // Format Kode Pelacak: WB-YYYY-XXXXXXXX (8 karakter acak)
                $trackingCode = 'WB-' . date('Y') . '-' . strtoupper(\Illuminate\Support\Str::random(8));
                $emailValue = strtolower($trackingCode) . $domainDummy;
                
                $customer = Customer::create(
                    $emailValue,
                    [
                        'first_name' => $nameValue,
                        'last_name' => '',
                        'email' => $emailValue,
                    ]
                );

                $emailRow = Email::whereRaw('LOWER(email) = ?', [$emailValue])->first();
                if ($emailRow) {
                    EndUserPortalAccount::create([
                        'customer_id' => $customer->id,
                        'email_id' => $emailRow->id,
                        'auth_type' => 'password',
                        'password' => \Illuminate\Support\Facades\Hash::make($trackingCode),
                        'sso_provider' => null,
                        'sso_id' => null,
                        'verification_token' => null,
                        'email_verified_at' => now(),
                    ]);
                }

                // Simpan kode pelacak ke session untuk ditampilkan di halaman sukses
                if ($reportType === 'anonim') {
                    session()->flash('secret_tracking_code', $trackingCode);
                } else {
                    // Masih menggunakan tracking code tapi bukan anonim murni
                    session()->flash('secret_tracking_code_terbuka', $trackingCode);
                }
            }
        }

        /*
     * Buat conversation awal.
     */
        $conversation = new Conversation();
        $conversation->type = Conversation::TYPE_EMAIL;
        $conversation->subject = $subject;
        $conversation->customer_id = $customer->id;
        $conversation->mailbox_id = $mailbox->id;
        $conversation->status = Conversation::STATUS_ACTIVE;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->source_via = Conversation::PERSON_CUSTOMER;

        if (
            Schema::hasColumn(
                'conversations',
                'customer_email'
            )
        ) {
            $conversation->customer_email = $emailValue;
        }

        $conversation->last_reply_at = now();
        $conversation->last_reply_from =
            Conversation::PERSON_CUSTOMER;
        $conversation->user_updated_at = now();

        /*
     * Preview sementara sebelum thread dibuat.
     */
        $conversation->setPreview(
            (string) $request->input('message')
        );

        /*
     * Simpan conversation agar ID tersedia.
     */
        $conversation->save();

        /*
     * Susun body thread.
     */
        $body = nl2br(
            e((string) $request->input('message'))
        );

        /*
     * Buat thread pertama.
     */
        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->customer_id = $customer->id;
        $thread->type = Thread::TYPE_CUSTOMER;
        $thread->status = Thread::STATUS_ACTIVE;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->body = $body;
        $thread->source_via = Thread::PERSON_CUSTOMER;
        $thread->from = $emailValue;
        $thread->to = json_encode(
            $mailbox->getEmails()
        );
        $thread->first = true;
        $thread->save();


        /*
 * Simpan attachment sebagai attachment asli FreeScout
 * sekaligus menyiapkan datanya untuk NotificationService.
 */
        $hasAttachments = false;
        $notificationFiles = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachment = Attachment::create(
                    $file->getClientOriginalName(),
                    $file->getMimeType(),
                    null,
                    null,
                    $file,
                    false,
                    $thread->id,
                    null
                );

                $hasAttachments = true;

                $attachmentPath = $attachment->getLocalFilePath();

                if (
                    $attachment->fileExists()
                    && $attachmentPath
                    && is_file($attachmentPath)
                ) {
                    $notificationFiles[] = [
                        'attachment_id' => $attachment->id,
                        'path'          => $attachmentPath,
                        'name'          => $attachment->file_name,
                        'mime_type'     => $attachment->mime_type,
                        'size'          => $attachment->size,
                        'temporary'     => false,
                    ];
                }
            }
        }

        if ($hasAttachments) {
            $thread->has_attachments = true;
            $thread->save();

            $conversation->has_attachments = true;
        }



        /*
 * Update conversation setelah thread dan attachment selesai.
 */
        $conversation->setPreview($body);
        $conversation->updateFolder();
        $conversation->save();

        /*
         * Trigger hook for modules (like Custom Fields) to save their data
         */
        \Eventy::action('portal.ticket.submitted', $conversation, $request, $setting, $mailbox);

        /*
 * Kirim notification channel hanya satu kali.
 */
        try {
            $this->sendNotificationChannels(
                $conversation,
                $thread,
                $notificationFiles
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send notification: ' . $e->getMessage());
        }

        /*
         * Kirim email konfirmasi dan tautan akses jika user memberikan email asli
         */
        $originalEmailInput = trim((string) $request->input('email'));
        if ($originalEmailInput !== '' && $reportType === 'terbuka') {
            $token = \Illuminate\Support\Str::random(60);
            \Illuminate\Support\Facades\Cache::put('track_token_' . $token, $conversation->number, now()->addHours(24));
            
            $url = route('PoliwangiPortal.end_user_portal.track.verify', ['token' => $token]);
            
            try {
                \App\Misc\Mail::setSystemMailDriver();
                \Illuminate\Support\Facades\Mail::send('poliwangiportal::emails.tracking_link', ['url' => $url, 'conversation' => $conversation], function ($message) use ($originalEmailInput, $conversation) {
                    $message->to($originalEmailInput)
                            ->subject('Laporan Diterima - Tautan Akses Pelacakan #' . $conversation->number);
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send confirmation email: ' . $e->getMessage());
            }
        }

        $mailbox->updateFoldersCounters();

        return redirect()
            ->route(
                'PoliwangiPortal.end_user_portal.submit_ticket',
                $mailbox->id
            )
            ->with(
                'success',
                'Laporan berhasil dikirim.'
            )
            ->with('ticket_number', $conversation->number)
            ->with('ticket_subject', $conversation->subject);
    }

    /**
     * Menampilkan semua ticket milik end user dari seluruh mailbox.
     * Cek Status dibuat global, tidak dibatasi per mailbox.
     */
    public function myTickets()
    {
        if (!session()->has('end_user_portal_email')) {
            return view('poliwangiportal::end_user_portal.track_ticket');
        }

        $email = strtolower(trim(session('end_user_portal_email')));

        /*
     * Ambil semua customer_id berdasarkan email login.
     */
        $customerIds = Email::whereRaw('LOWER(email) = ?', [$email])
            ->pluck('customer_id')
            ->toArray();

        if (empty($customerIds)) {
            $tickets = [];

            return view('poliwangiportal::end_user_portal.my_ticket', compact(
                'email',
                'tickets'
            ));
        }

        /*
     * Ambil semua ticket milik pelapor dari seluruh mailbox.
     */
        $conversations = Conversation::whereIn('customer_id', $customerIds)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->orderBy('updated_at', 'desc')
            ->get();

        $mailboxIds = $conversations->pluck('mailbox_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $mailboxes = Mailbox::whereIn('id', $mailboxIds)
            ->get()
            ->keyBy('id');

        $settingsByMailbox = EndUserPortalSetting::whereIn('mailbox_id', $mailboxIds)
            ->get()
            ->keyBy('mailbox_id');

        $tickets = [];

        foreach ($conversations as $conversation) {
            /*
         * Ambil thread pertama untuk preview.
         */
            $thread = Thread::where('conversation_id', $conversation->id)
                ->where('state', Thread::STATE_PUBLISHED)
                ->orderBy('id', 'asc')
                ->first();

            $preview = '';

            if ($thread) {
                $fullPreview = trim(strip_tags($thread->body));
                $preview = mb_substr($fullPreview, 0, 120);

                if (mb_strlen($fullPreview) > 120) {
                    $preview .= '...';
                }
            }

            /*
         * Status ticket.
         */
            $status = 'Open';

            if ($conversation->status == Conversation::STATUS_CLOSED) {
                $status = 'Closed';
            }

            /*
         * Last activity.
         */
            $lastActivity = '-';

            if ($conversation->last_reply_at) {
                $lastActivity = $conversation->last_reply_at->format('M d, Y');
            } elseif ($conversation->updated_at) {
                $lastActivity = $conversation->updated_at->format('M d, Y');
            }

            /*
         * Jumlah thread.
         */
            $threadCount = Thread::where('conversation_id', $conversation->id)
                ->where('state', Thread::STATE_PUBLISHED)
                ->count();

            $mailbox = $mailboxes->get($conversation->mailbox_id);
            $setting = $settingsByMailbox->get($conversation->mailbox_id);

            $tickets[] = [
                'id' => $conversation->id,
                'mailbox_id' => $conversation->mailbox_id,
                'mailbox_name' => $mailbox ? $mailbox->name : '-',
                'number' => $conversation->number,
                'subject' => $conversation->subject ?: 'No Subject',
                'preview' => $preview,
                'status' => $status,
                'last_activity' => $lastActivity,
                'count' => $threadCount,
                'show_ticket_number' => !empty($setting) && !empty($setting->show_ticket_numbers),
            ];
        }

        return view('poliwangiportal::end_user_portal.my_ticket', compact(
            'email',
            'tickets'
        ));
    }

    /**
     * Menampilkan detail ticket untuk end user.
     */
    public function ticketDetail($mailbox_id, $conversation_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $setting = EndUserPortalSetting::where('mailbox_id', $mailbox->id)->first();

        $sessionKey = 'end_user_portal_email_' . $mailbox->id;

        if (!session()->has('end_user_portal_email')) {
            return redirect()
                ->route('PoliwangiPortal.end_user_portal.login_end_user', [
                    'redirect' => route('PoliwangiPortal.end_user_portal.ticket_detail', [
                        $mailbox->id,
                        $conversation_id,
                    ]),
                ])
                ->withErrors([
                    'email' => 'Silakan login terlebih dahulu untuk melihat tiket Anda.',
                ]);
        }

        $email = strtolower(trim(session('end_user_portal_email')));

        /*
         * Ambil customer_id berdasarkan email login.
         */
        $customerIds = Email::whereRaw('LOWER(email) = ?', [$email])
            ->pluck('customer_id')
            ->toArray();

        /*
         * Pastikan conversation ini milik email login dan mailbox yang benar.
         * Ini penting agar user tidak bisa membuka tiket orang lain lewat URL.
         */
        $conversation = Conversation::where('id', $conversation_id)
            ->where('mailbox_id', $mailbox->id)
            ->whereIn('customer_id', $customerIds)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->firstOrFail();

        $threads = Thread::where('conversation_id', $conversation->id)
            ->where('state', Thread::STATE_PUBLISHED)
            ->orderBy('created_at', 'desc')
            ->get();

        $ratingSetting = null;
        $ratings = collect();

        // @codeCoverageIgnoreStart
        if (class_exists('\Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting')) {
            $settingModel = new \Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting();
            if (\Illuminate\Support\Facades\Schema::hasTable($settingModel->getTable())) {
                $ratingSetting = \Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();
            }
            
            if ($ratingSetting && class_exists('\Modules\PoliwangiSatisfaction\Models\SatisfactionRating')) {
                $ratingModel = new \Modules\PoliwangiSatisfaction\Models\SatisfactionRating();
                if (\Illuminate\Support\Facades\Schema::hasTable($ratingModel->getTable())) {
                    $ratings = \Modules\PoliwangiSatisfaction\Models\SatisfactionRating::where('mailbox_id', $mailbox->id)
                        ->where('conversation_id', $conversation->id)
                        ->where('email', $email)
                        ->get()
                        ->keyBy('thread_id');
                }
            }
        }
        // @codeCoverageIgnoreEnd

        return view('poliwangiportal::end_user_portal.ticket_detail', compact(
            'mailbox',
            'setting',
            'email',
            'conversation',
            'threads',
            'ratingSetting',
            'ratings'
        ));
    }

    /**
     * End user membalas ticket.
     */
    public function replyTicket(Request $request, $mailbox_id, $conversation_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $setting = EndUserPortalSetting::where('mailbox_id', $mailbox->id)->first();

        $sessionKey = 'end_user_portal_email_' . $mailbox->id;

        if (!session()->has('end_user_portal_email')) {
            return redirect()
                ->route('PoliwangiPortal.end_user_portal.login_end_user', [
                    'redirect' => route('PoliwangiPortal.end_user_portal.ticket_detail', [
                        $mailbox->id,
                        $conversation_id,
                    ]),
                ])
                ->withErrors([
                    'email' => 'Silakan login terlebih dahulu untuk membalas tiket.',
                ]);
        }

        $email = strtolower(trim(session('end_user_portal_email')));

        $request->validate([
            'message' => 'required|string',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:' . floor(\Illuminate\Http\UploadedFile::getMaxFilesize() / 1024) . '|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip,rar',
        ], $this->validationMessages());

        $customerIds = Email::whereRaw('LOWER(email) = ?', [$email])
            ->pluck('customer_id')
            ->toArray();

        $conversation = Conversation::where('id', $conversation_id)
            ->where('mailbox_id', $mailbox->id)
            ->whereIn('customer_id', $customerIds)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->firstOrFail();

        if ($conversation->status == Conversation::STATUS_CLOSED) {
            return redirect()
                ->route('PoliwangiPortal.end_user_portal.ticket_detail', [$mailbox->id, $conversation->id])
                ->withErrors([
                    'message' => 'Ticket sudah closed dan tidak bisa dibalas.',
                ]);
        }

        $body = nl2br(e($request->message));

        $customer = $conversation->customer;

        /*
         * Buat thread balasan customer.
         */
        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->customer_id = $customer ? $customer->id : null;
        $thread->type = Thread::TYPE_CUSTOMER;
        $thread->status = Thread::STATUS_ACTIVE;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->body = $body;
        $thread->source_via = Thread::PERSON_CUSTOMER;
        $thread->from = $email;
        $thread->to = json_encode($mailbox->getEmails());
        $thread->save();


        /*
         * Simpan attachment sebagai attachment asli FreeScout.
         */
        $hasAttachments = false;

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachment = Attachment::create(
                    $file->getClientOriginalName(),
                    $file->getMimeType(),
                    null,
                    null,
                    $file,
                    false,
                    $thread->id,
                    null
                );

                if ($attachment) {
                    $hasAttachments = true;
                }
            }
        }

        if ($hasAttachments) {
            $thread->has_attachments = true;
            $thread->save();

            $conversation->has_attachments = true;
        }

        /*
         * Update conversation supaya naik ke atas list.
         */
        $conversation->status = Conversation::STATUS_ACTIVE;
        $conversation->last_reply_at = now();
        $conversation->last_reply_from = Conversation::PERSON_CUSTOMER;
        $conversation->setPreview($body);
        $conversation->updateFolder();
        $conversation->save();

        $conversation->mailbox->updateFoldersCounters();

        return redirect()
            ->route('PoliwangiPortal.end_user_portal.ticket_detail', [$mailbox->id, $conversation->id])
            ->with('success', 'Balasan berhasil dikirim.');
    }

    /**
     * Menampilkan halaman register end user.
     */
    public function registerEndUser(Request $request)
    {
        if (session()->has('end_user_portal_email')) {
            return redirect($this->safeRedirect($request->input('redirect', url('/help'))));
        }

        $redirect = $request->input('redirect', url('/help'));

        return view('poliwangiportal::end_user_portal.register_end_user', compact(
            'redirect'
        ));
    }

    /**
     * Proses register end user.
     */
    public function registerEndUserSubmit(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'redirect' => 'nullable|string',
        ];
        
        // @codeCoverageIgnoreStart
        if (!app()->runningUnitTests()) {
            $rules['captcha'] = 'required|captcha';
        }
        // @codeCoverageIgnoreEnd
        
        $request->validate($rules, $this->validationMessages());



        $emailValue = strtolower(trim((string) $request->input('email')));
        $redirect = $this->safeRedirect($request->input('redirect', url('/help')));

        $emailRow = Email::whereRaw(
            'LOWER(email) = ?',
            [$emailValue]
        )->first();

        if ($emailRow) {
            $hasAccount = EndUserPortalAccount::where('email_id', $emailRow->id)
                ->exists();

            if ($hasAccount) {
                return redirect()
                    ->back()
                    ->withInput($request->only('name', 'email', 'redirect'))
                    ->withErrors([
                        'email' => 'Email ini sudah terdaftar. Silakan login.',
                    ]);
            }
        }

        if ($emailRow && $emailRow->customer) {
            $customer = $emailRow->customer;
        } else {
            $customer = Customer::create(
                $emailValue,
                [
                    'first_name' => trim((string) $request->input('name')),
                    'last_name' => '',
                    'email' => $emailValue,
                    'channel' => 'end_user_portal',
                    'channel_id' => null,
                ]
            );

            $emailRow = Email::whereRaw(
                'LOWER(email) = ?',
                [$emailValue]
            )->first();
        }


        $token = Str::random(60);

        $account = EndUserPortalAccount::create([
            'customer_id' => $customer->id,
            'email_id' => $emailRow->id,
            'auth_type' => 'password',
            'password' => Hash::make($request->input('password')),
            'sso_provider' => null,
            'sso_id' => null,
            'verification_token' => $token,
            'email_verified_at' => null,
        ]);

        $verificationUrl = route('PoliwangiPortal.end_user_portal.verify', ['token' => $token, 'redirect' => $redirect]);

        try {
            \App\Misc\Mail::setSystemMailDriver();
            Mail::send('poliwangiportal::emails.verification', ['url' => $verificationUrl], function ($message) use ($emailValue) {
                $message->to($emailValue)
                        ->subject('Verifikasi Akun Portal Poliwangi Portal');
            });
            $msg = 'Akun berhasil dibuat. Silakan cek kotak masuk email Anda (' . $emailValue . ') untuk melakukan verifikasi akun sebelum login.';
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
            $msg = 'Akun berhasil dibuat, namun sistem gagal mengirim email verifikasi saat ini. Silakan coba login nanti untuk mengirim ulang link verifikasi.';
        }

        return redirect()
            ->route('PoliwangiPortal.end_user_portal.login_end_user', [
                'redirect' => $redirect,
            ])
            ->with('success', $msg);
    }

    /**
     * Memverifikasi email berdasarkan token.
     */
    public function verifyEmail(Request $request, $token)
    {
        $account = EndUserPortalAccount::where('verification_token', $token)->first();

        if (!$account) {
            return redirect()
                ->route('PoliwangiPortal.end_user_portal.login_end_user')
                ->withErrors(['email' => 'Link verifikasi tidak valid atau sudah kadaluarsa.']);
        }

        $account->update([
            'email_verified_at' => now(),
            'verification_token' => null,
        ]);

        $emailRow = Email::find($account->email_id);

        if ($emailRow) {
            session([
                'end_user_portal_email' => $emailRow->email,
                'end_user_portal_customer_id' => $account->customer_id,
            ]);
        }

        $redirect = $this->safeRedirect($request->input('redirect', url('/help')));

        return redirect($redirect)->with('success', 'Email berhasil diverifikasi. Anda telah otomatis login.');
    }

    /**
     * Menampilkan halaman login end user.
     */
    public function loginEndUser(Request $request)
    {
        if (session()->has('end_user_portal_email')) {
            return redirect($this->safeRedirect($request->input('redirect', url('/help'))));
        }

        $redirect = $request->input('redirect', url('/help'));

        return view('poliwangiportal::end_user_portal.login_end_user', compact(
            'redirect'
        ));
    }

    /**
     * Proses login end user.
     */
    public function loginEndUserSubmit(Request $request)
    {
        $rules = [
            'email' => 'required|email|max:255',
            'password' => 'required|string',
            'redirect' => 'nullable|string',
        ];
        
        // @codeCoverageIgnoreStart
        if (!app()->runningUnitTests()) {
            $rules['captcha'] = 'required|captcha';
        }
        // @codeCoverageIgnoreEnd
        
        $request->validate($rules, $this->validationMessages());



        $emailValue = strtolower(trim((string) $request->input('email')));
        $redirect = $this->safeRedirect($request->input('redirect', url('/help')));

        $emailRow = Email::whereRaw(
            'LOWER(email) = ?',
            [$emailValue]
        )->first();

        if (!$emailRow) {
            return redirect()
                ->back()
                ->withInput($request->only('email', 'redirect'))
                ->withErrors([
                    'email' => 'Email belum terdaftar pada sistem. Silakan gunakan email yang telah terdaftar.',
                ]);
        }

        $account = EndUserPortalAccount::where('email_id', $emailRow->id)
            ->where('auth_type', 'password')
            ->first();

        if (!$account) {
            return redirect()
                ->back()
                ->withInput($request->only('email', 'redirect'))
                ->withErrors([
                    'email' => 'Email belum terdaftar pada sistem. Silakan gunakan email yang telah terdaftar.',
                ]);
        }

        if (!Hash::check($request->input('password'), $account->password)) {
            return redirect()
                ->back()
                ->withInput($request->only('email', 'redirect'))
                ->withErrors([
                    'email' => 'Password salah.',
                ]);
        }

        if (is_null($account->email_verified_at)) {
            $token = $account->verification_token;
            if (!$token) {
                $token = \Illuminate\Support\Str::random(60);
                $account->verification_token = $token;
                $account->save();
            }

            $verificationUrl = route('PoliwangiPortal.end_user_portal.verify', ['token' => $token, 'redirect' => $redirect]);

            try {
                \App\Misc\Mail::setSystemMailDriver();
                \Illuminate\Support\Facades\Mail::send('poliwangiportal::emails.verification', ['url' => $verificationUrl], function ($message) use ($emailValue) {
                    $message->to($emailValue)
                            ->subject('Verifikasi Akun Portal Poliwangi Portal');
                });
                
                $msg = 'Akun Anda belum diverifikasi. Kami telah mengirim ulang link verifikasi ke email Anda. Silakan cek kotak masuk atau folder spam.';
            // @codeCoverageIgnoreStart
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to resend verification email: ' . $e->getMessage());
                $msg = 'Sistem gagal mengirim ulang link verifikasi. Pastikan konfigurasi email pada pengaturan sistem sudah benar atau coba lagi nanti.';
            }
            // @codeCoverageIgnoreEnd

            return redirect()
                ->back()
                ->withInput($request->only('email', 'redirect'))
                ->withErrors([
                    'email' => $msg,
                ]);
        }

        session([
            'end_user_portal_email' => $emailRow->email,
            'end_user_portal_customer_id' => $account->customer_id,
        ]);

        return redirect($redirect);
    }

    /**
     * Logout end user.
     */
    public function logoutEndUser(Request $request)
    {
        session()->forget('end_user_portal_email');
        session()->forget('end_user_portal_customer_id');

        return redirect($this->safeRedirect($request->input('redirect', url('/help'))))
            ->with('success', 'Berhasil logout.');
    }


    /**
     * Mengirim notifikasi ticket baru melalui seluruh channel aktif.
     *
     * Controller hanya memilih channel dan menyusun data umum.
     * Detail Telegram, WhatsApp, atau platform lain ditangani
     * oleh NotificationService dan sender masing-masing.
     */
    private function sendNotificationChannels(
        Conversation $conversation,
        Thread $thread,
        array $notificationFiles = []
    ) {
        $channels = NotificationChannel::query()
            ->where('is_active', true)
            ->where(function ($query) use ($conversation) {
                $query
                    ->whereNull('mailbox_id')
                    ->orWhere(
                        'mailbox_id',
                        $conversation->mailbox_id
                    );
            })
            ->get();

        if ($channels->isEmpty()) {
            return;
        }

        $mailbox = $conversation->mailbox;
        // @codeCoverageIgnoreStart
        if (!$mailbox) {
            $mailbox = Mailbox::find($conversation->mailbox_id);
        }
        // @codeCoverageIgnoreEnd

        $customer = $conversation->customer;
        // @codeCoverageIgnoreStart
        if (!$customer) {
            $customer = Customer::find($conversation->customer_id);
        }
        // @codeCoverageIgnoreEnd

        $mailboxName = '-';
        if ($mailbox) {
            $mailboxName = trim((string) $mailbox->name);
            // @codeCoverageIgnoreStart
            if ($mailboxName === '') {
                $mailboxName = '-';
            }
            // @codeCoverageIgnoreEnd
        }

        $customerName = '-';

        if ($customer) {
            $customerName = trim(
                (string) $customer->first_name
                    . ' '
                    . (string) $customer->last_name
            );

            // @codeCoverageIgnoreStart
            if ($customerName === '') {
                $customerName = '-';
            }
            // @codeCoverageIgnoreEnd
        }

        $customerEmail = $this->resolveConversationEmail(
            $conversation,
            $thread
        );

        $subject = trim(
            (string) (
                $conversation->subject
                ?: 'Tanpa Subjek'
            )
        );

        $messageBody = $this->normalizeNotificationBody(
            $thread->body
        );

        $detailUrl = route(
            'conversations.view',
            [
                'id' => $conversation->id,
            ]
        );

        $message = "📩 Laporan Baru Masuk\n\n";
        $message .= "Mailbox: {$mailboxName}\n";
        $message .= "Subjek: {$subject}\n";
        $message .= "Pelapor: {$customerName}\n";
        $message .= "Email: {$customerEmail}\n\n";
        $message .= "Pesan:\n{$messageBody}";

        $options = [
            'event' => 'ticket_created',

            'conversation_id' => $conversation->id,
            'thread_id'       => $thread->id,
            'mailbox_id'      => $conversation->mailbox_id,

            'subject'    => $subject,
            'detail_url' => $detailUrl,

            'attachments' => $notificationFiles,

            'actions' => [
                [
                    'label' => '🔎 Buka Detail',
                    'type'  => 'url',
                    'url'   => $detailUrl,
                ],
                [
                    'label' => '✅ Active',
                    'type'  => 'callback',
                    'value' => 'active_conversation_' . $conversation->id,
                ],
                [
                    'label' => '⏳ Pending',
                    'type'  => 'callback',
                    'value' => 'pending_conversation_' . $conversation->id,
                ],
                [
                    'label' => '🔒 Closed',
                    'type'  => 'callback',
                    'value' => 'close_conversation_' . $conversation->id,
                ],
                [
                    'label' => '🚫 Spam',
                    'type'  => 'callback',
                    'value' => 'spam_conversation_' . $conversation->id,
                ],
            ],
        ];

        foreach ($channels as $channel) {
            $result = $this->notificationService->send(
                $channel,
                null,
                $message,
                $options
            );

            // @codeCoverageIgnoreStart
            if ($result === false) {
                Log::warning(
                    'Notification sender mengembalikan nilai false.',
                    [
                        'channel_id'      => $channel->id,
                        'channel_type'    => $channel->type,
                        'conversation_id' => $conversation->id,
                    ]
                );
            }
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Mengambil email customer untuk isi notifikasi.
     */
    private function resolveConversationEmail(
        Conversation $conversation,
        Thread $thread
    ) {
        if (
            Schema::hasColumn(
                'conversations',
                'customer_email'
            )
            && !empty($conversation->customer_email)
        ) {
            return trim(
                (string) $conversation->customer_email
            );
        }

        if (!empty($thread->from)) {
            return trim(
                (string) $thread->from
            );
        }

        // @codeCoverageIgnoreStart
        $customer = $conversation->customer;

        if (!$customer) {
            $customer = Customer::find(
                $conversation->customer_id
            );
        }

        if (!$customer) {
            return '-';
        }

        /*
     * Hindari memanggil method yang belum tentu ada
     * pada versi FreeScout yang digunakan.
     */
        $emailRow = Email::where(
            'customer_id',
            $customer->id
        )
            ->orderBy('id', 'asc')
            ->first();

        if ($emailRow && !empty($emailRow->email)) {
            return trim(
                (string) $emailRow->email
            );
        }

        return '-';
        // @codeCoverageIgnoreEnd
    }

    /**
     * Membersihkan isi thread sebelum dimasukkan ke notifikasi.
     */
    private function normalizeNotificationBody($body)
    {
        if ($body === null || $body === '') {
            return '-';
        }

        $normalizedBody = strip_tags(
            (string) $body
        );

        $normalizedBody = html_entity_decode(
            $normalizedBody,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $normalizedBody = trim(
            $normalizedBody
        );

        if ($normalizedBody === '') {
            return '-';
        }

        if (mb_strlen($normalizedBody) > 1000) {
            return mb_substr(
                $normalizedBody,
                0,
                1000
            ) . '...';
        }

        return $normalizedBody;
    }



    /**
     * Redirect awal ke SSO Poliwangi.
     * Untuk sementara masih placeholder sampai endpoint SSO asli dari kampus tersedia.
     */
    public function redirectToPoliwangiSso(Request $request)
    {
        $redirect = $this->safeRedirect($request->input('redirect', url('/help')));
        $state = bin2hex(random_bytes(16));
        
        session([
            'end_user_portal_sso_redirect' => $redirect,
            'oauth_state' => $state,
            'oauth_portal' => true,
        ]);

        $query = http_build_query([
            'client_id'     => config('poliwangisso.oidc.client_id'),
            'redirect_uri'  => config('poliwangisso.oidc.redirect_uri') ?: route('poliwangisso.callback'),
            'response_type' => 'code',
            'scope'         => config('poliwangisso.oidc.scope', ''),
            'state'         => $state,
        ]);

        return redirect(config('poliwangisso.oidc.url_authorize') . '?' . $query);
    }

    /**
     * Callback SSO Poliwangi.
     */
    public function handlePoliwangiSsoCallback(Request $request)
    {
        $redirect = session('end_user_portal_sso_redirect', url('/help'));
        
        $state = session()->pull('oauth_state');
        session()->forget('oauth_portal');
        if (empty($state) || $state !== $request->get('state')) {
            return redirect()->route('PoliwangiPortal.end_user_portal.login_end_user')->withErrors(['email' => 'Invalid OAuth state.']);
        }

        if ($request->has('error')) {
            return redirect()->route('PoliwangiPortal.end_user_portal.login_end_user')->withErrors(['email' => 'OAuth Authorization denied.']);
        }

        // 1. Ambil Access Token
        $response = \Illuminate\Support\Facades\Http::asForm()->post(config('poliwangisso.oidc.url_access_token'), [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('poliwangisso.oidc.client_id'),
            'client_secret' => config('poliwangisso.oidc.client_secret'),
            'redirect_uri'  => config('poliwangisso.oidc.redirect_uri') ?: route('poliwangisso.callback'),
            'code'          => $request->get('code'),
        ]);

        if (!$response->successful()) {
            return redirect()->route('PoliwangiPortal.end_user_portal.login_end_user')->withErrors(['email' => 'Gagal mengambil access token dari SSO.']);
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];

        // 2. Ambil Profil User
        $userResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)->get(config('poliwangisso.oidc.url_resource_owner_details'));

        if (!$userResponse->successful()) {
            return redirect()->route('PoliwangiPortal.end_user_portal.login_end_user')->withErrors(['email' => 'Gagal mengambil data profil dari SSO.']);
        }

        $remoteUser = $userResponse->json();
        $emailValue = strtolower(trim($remoteUser['email'] ?? ''));

        if (!$emailValue) {
            return redirect()->route('PoliwangiPortal.end_user_portal.login_end_user')->withErrors(['email' => 'Email tidak diberikan oleh server SSO.']);
        }

        // 3. Sync ke FreeScout Customer & EndUserPortalAccount
        $emailRow = Email::whereRaw('LOWER(email) = ?', [$emailValue])->first();

        if ($emailRow && $emailRow->customer) {
            $customer = $emailRow->customer;
        } else {
            $customer = Customer::create(
                $emailValue,
                [
                    'first_name' => $remoteUser['name'] ?? 'SSO User',
                    'last_name' => '',
                    'email' => $emailValue,
                    'channel' => 'end_user_portal',
                    'channel_id' => null,
                ]
            );

            $emailRow = Email::whereRaw('LOWER(email) = ?', [$emailValue])->first();
        }

        $account = EndUserPortalAccount::where('email_id', $emailRow->id)->first();

        if (!$account) {
            $account = EndUserPortalAccount::create([
                'customer_id' => $customer->id,
                'email_id' => $emailRow->id,
                'auth_type' => 'sso',
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24)),
                'sso_provider' => 'poliwangi',
                'sso_id' => $remoteUser['id'] ?? null,
                'verification_token' => null,
                'email_verified_at' => now(), // SSO diabaikan verifikasi manual
            ]);
        } else {
            // Update jika sebelumnya manual, bisa login via SSO juga
            if (is_null($account->email_verified_at)) {
                $account->update(['email_verified_at' => now(), 'verification_token' => null]);
            }
        }

        // 4. Log in dengan Session
        session([
            'end_user_portal_email' => $emailRow->email,
            'end_user_portal_customer_id' => $account->customer_id,
        ]);

        return redirect($redirect)->with('success', 'Berhasil login melalui SSO Poliwangi.');
    }
    public function trackTicketSubmit(Request $request)
    {
        $rateLimitKey = 'track_ticket_' . str_replace(':', '_', $request->ip());
        if (\Illuminate\Support\Facades\Cache::has($rateLimitKey)) {
            $attempts = \Illuminate\Support\Facades\Cache::increment($rateLimitKey);
            if ($attempts > 5) {
                return back()->withInput()->withErrors(['message' => 'Terlalu banyak percobaan. Silakan coba lagi setelah 1 menit.']);
            }
        } else {
            \Illuminate\Support\Facades\Cache::put($rateLimitKey, 1, now()->addMinutes(1));
        }

        if ($request->has('tracking_code')) {
            $request->validate([
                'tracking_code' => 'required|string',
            ], [
                'tracking_code.required' => 'Kode Pelacak wajib diisi.',
            ]);

            $emailValue = strtolower(trim($request->tracking_code)) . '@anonim.local';
            
            $emailRow = Email::whereRaw('LOWER(email) = ?', [$emailValue])->first();
            if (!$emailRow) {
                return back()->withInput()->withErrors(['tracking_code' => 'Kode Pelacak tidak valid atau tiket tidak ditemukan.']);
            }

            $conversation = Conversation::where('customer_id', $emailRow->customer_id)
                ->where('state', Conversation::STATE_PUBLISHED)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$conversation) {
                return back()->withInput()->withErrors(['tracking_code' => 'Tiket tidak ditemukan.']);
            }

            // Autentikasi sesi sementara untuk melihat tiket ini
            session(['tracking_authenticated_ticket' => $conversation->number]);
            
            return redirect()->route('PoliwangiPortal.end_user_portal.track_detail', $conversation->number);
        }

        $request->validate([
            'ticket_number' => 'required|numeric',
            'email' => 'required|email',
        ], [
            'ticket_number.required' => 'Nomor tiket wajib diisi.',
            'ticket_number.numeric' => 'Nomor tiket harus berupa angka.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $conversation = Conversation::where(Conversation::numberFieldName(), $request->ticket_number)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->first();

        if (!$conversation) {
            return back()->withInput()->withErrors(['ticket_number' => 'Tiket dengan nomor tersebut tidak ditemukan.']);
        }

        $customerIds = Email::whereRaw('LOWER(email) = ?', [strtolower(trim($request->email))])
            ->pluck('customer_id')
            ->toArray();
            
        $emailMatches = false;
        if (Schema::hasColumn('conversations', 'customer_email') && strtolower($conversation->customer_email ?? '') === strtolower(trim($request->email))) {
            $emailMatches = true;
        } elseif (in_array($conversation->customer_id, $customerIds)) {
            $emailMatches = true;
        }

        if (!$emailMatches) {
             return back()->withInput()->withErrors(['email' => 'Email tidak sesuai dengan pembuat tiket ini.']);
        }

        $token = Str::random(60);
        \Illuminate\Support\Facades\Cache::put('track_token_' . $token, $conversation->number, now()->addHours(24));
        
        $url = route('PoliwangiPortal.end_user_portal.track.verify', ['token' => $token]);
        
        try {
            \App\Misc\Mail::setSystemMailDriver();
            Mail::send('poliwangiportal::emails.tracking_link', ['url' => $url, 'conversation' => $conversation], function ($message) use ($request, $conversation) {
                $message->to($request->email)
                        ->subject('Tautan Akses Pelacakan Laporan - #' . $conversation->number);
            });
        // @codeCoverageIgnoreStart
        } catch (\Throwable $e) {
            Log::error('Gagal kirim email magic link: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Gagal mengirim email tautan. Silakan coba lagi nanti.']);
        }
        // @codeCoverageIgnoreEnd

        return back()->with('success', 'Tautan pelacakan yang aman telah dikirim ke email Anda. Silakan cek Kotak Masuk / Spam Anda.');
    }

    /**
     * Verifikasi token tautan pelacakan dari email.
     */
    public function verifyTrackingToken($token)
    {
        $ticketNumber = \Illuminate\Support\Facades\Cache::get('track_token_' . $token);
        
        if (!$ticketNumber) {
            return redirect()->route('PoliwangiPortal.end_user_portal.my_ticket')->withErrors(['message' => 'Tautan pelacakan tidak valid atau sudah kadaluarsa.']);
        }
        
        session(['tracking_authenticated_ticket' => $ticketNumber]);
        
        return redirect()->route('PoliwangiPortal.end_user_portal.track_detail', $ticketNumber);
    }

    private function verifyTrackingAccess(Conversation $conversation)
    {
        if (session('tracking_authenticated_ticket') == $conversation->number) {
            return true;
        }
        
        if (session()->has('end_user_portal_email')) {
            $email = strtolower(trim(session('end_user_portal_email')));
            if (Schema::hasColumn('conversations', 'customer_email') && strtolower($conversation->customer_email ?? '') === $email) {
                return true;
            }
            
            $customerIds = Email::whereRaw('LOWER(email) = ?', [$email])
                ->pluck('customer_id')
                ->toArray();
                
            if (in_array($conversation->customer_id, $customerIds)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Menampilkan detail tiket berdasarkan nomor tiket (Track).
     */
    public function trackTicketDetail($number)
    {
        $conversation = Conversation::where(Conversation::numberFieldName(), $number)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->firstOrFail();

        if (!$this->verifyTrackingAccess($conversation)) {
            return redirect()->route('PoliwangiPortal.end_user_portal.my_ticket')
                ->withErrors(['message' => 'Anda tidak memiliki akses ke tiket ini atau sesi pelacakan Anda telah berakhir. Silakan masukkan ulang Nomor Tiket.']);
        }

        $mailbox = Mailbox::findOrFail($conversation->mailbox_id);
        $setting = EndUserPortalSetting::where('mailbox_id', $mailbox->id)->first();
        
        $threads = Thread::where('conversation_id', $conversation->id)
            ->where('state', Thread::STATE_PUBLISHED)
            ->orderBy('created_at', 'desc')
            ->get();

        $ratingSetting = null;
        $ratings = collect();

        // @codeCoverageIgnoreStart
        if (class_exists('\Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting')) {
            $settingModel = new \Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting();
            if (\Illuminate\Support\Facades\Schema::hasTable($settingModel->getTable())) {
                $ratingSetting = \Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();
            }
            
            if ($ratingSetting && class_exists('\Modules\PoliwangiSatisfaction\Models\SatisfactionRating')) {
                $ratingModel = new \Modules\PoliwangiSatisfaction\Models\SatisfactionRating();
                if (\Illuminate\Support\Facades\Schema::hasTable($ratingModel->getTable())) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('conversations', 'customer_email') && $conversation->customer_email) {
                        $ratings = \Modules\PoliwangiSatisfaction\Models\SatisfactionRating::where('mailbox_id', $mailbox->id)
                            ->where('conversation_id', $conversation->id)
                            ->where('email', $conversation->customer_email)
                            ->get()
                            ->keyBy('thread_id');
                    } elseif ($conversation->customer && $conversation->customer->getMainEmail()) {
                        $ratings = \Modules\PoliwangiSatisfaction\Models\SatisfactionRating::where('mailbox_id', $mailbox->id)
                            ->where('conversation_id', $conversation->id)
                            ->where('email', $conversation->customer->getMainEmail())
                            ->get()
                            ->keyBy('thread_id');
                    }
                }
            }
        }
        // @codeCoverageIgnoreEnd

        return view('poliwangiportal::end_user_portal.track_ticket_detail', compact(
            'mailbox',
            'setting',
            'conversation',
            'threads',
            'ratingSetting',
            'ratings'
        ));
    }

    /**
     * Membalas tiket dari halaman track tiket.
     */
    public function trackTicketReply(Request $request, $number)
    {
        $conversation = Conversation::where(Conversation::numberFieldName(), $number)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->firstOrFail();

        if (!$this->verifyTrackingAccess($conversation)) {
            return redirect()->route('PoliwangiPortal.end_user_portal.my_ticket')
                ->withErrors(['message' => 'Anda tidak memiliki akses ke tiket ini atau sesi pelacakan Anda telah berakhir.']);
        }

        if ($conversation->status == Conversation::STATUS_CLOSED) {
            return redirect()
                ->route('PoliwangiPortal.end_user_portal.track_detail', $number)
                ->withErrors([
                    'message' => 'Ticket sudah closed dan tidak bisa dibalas.',
                ]);
        }

        $request->validate([
            'message' => 'required|string',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:' . floor(\Illuminate\Http\UploadedFile::getMaxFilesize() / 1024) . '|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip,rar',
        ], $this->validationMessages());

        $mailbox = Mailbox::findOrFail($conversation->mailbox_id);
        $body = nl2br(e($request->message));
        $customer = $conversation->customer;
        
        $email = 'anonim@lapor.poliwangi';
        if (Schema::hasColumn('conversations', 'customer_email') && $conversation->customer_email) {
            $email = $conversation->customer_email;
        } elseif ($customer && $customer->getMainEmail()) {
            $email = $customer->getMainEmail();
        }

        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->customer_id = $customer ? $customer->id : null;
        $thread->type = Thread::TYPE_CUSTOMER;
        $thread->status = Thread::STATUS_ACTIVE;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->body = $body;
        $thread->source_via = Thread::PERSON_CUSTOMER;
        $thread->from = $email;
        $thread->to = json_encode($mailbox->getEmails());
        $thread->save();

        $hasAttachments = false;
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachment = Attachment::create(
                    $file->getClientOriginalName(), $file->getMimeType(), null, null, $file, false, $thread->id, null
                );
                if ($attachment) $hasAttachments = true;
            }
        }
        if ($hasAttachments) {
            $thread->has_attachments = true;
            $thread->save();
            $conversation->has_attachments = true;
        }

        $conversation->status = Conversation::STATUS_ACTIVE;
        $conversation->last_reply_at = now();
        $conversation->last_reply_from = Conversation::PERSON_CUSTOMER;
        $conversation->user_updated_at = now();
        $conversation->setPreview($body);
        $conversation->updateFolder();
        $conversation->save();

        return redirect()
            ->route('PoliwangiPortal.end_user_portal.track_detail', $number)
            ->with('success', 'Balasan berhasil dikirim.');
    }
}
