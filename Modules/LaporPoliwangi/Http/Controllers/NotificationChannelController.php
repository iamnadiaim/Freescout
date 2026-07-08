<?php

namespace Modules\LaporPoliwangi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\LaporPoliwangi\Models\NotificationChannel;
use Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory;
use Modules\LaporPoliwangi\Services\Notifications\NotificationService;
use Modules\LaporPoliwangi\Services\Notifications\NotificationWebhookFactory;

class NotificationChannelController extends Controller
{
    /**
     * @var NotificationSenderFactory
     */
    private $factory;

    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * @var NotificationWebhookFactory
     */
    private $webhookFactory;

    public function __construct(
        NotificationSenderFactory $factory,
        NotificationService $notificationService,
        NotificationWebhookFactory $webhookFactory
    ) {
        $this->middleware('auth')->except([
            'webhook',
        ]);

        $this->factory = $factory;
        $this->notificationService = $notificationService;
        $this->webhookFactory = $webhookFactory;
    }

    /**
     * Halaman Notification Channels tidak ditampilkan melalui method ini.
     *
     * Halaman ditampilkan oleh SettingsController@view agar layout,
     * navbar, dan menu Settings bawaan FreeScout tetap digunakan.
     *
     * Method ini hanya menjadi pengaman jika dipanggil langsung.
     */
    public function index()
    {
        return redirect()->route('settings', [
            'section' => 'notification_channels',
        ]);
    }

    /**
     * Menyimpan notification channel baru.
     */
    public function store(Request $request)
    {
        $validated = $this->validateChannel($request);

        try {
            $channel = new NotificationChannel();

            $channel->mailbox_id = !empty($validated['mailbox_id'])
                ? $validated['mailbox_id']
                : null;

            $channel->name = $validated['name'];
            $channel->type = $validated['type'];

            /*
             * Model NotificationChannel sudah menggunakan cast:
             *
             * protected $casts = [
             *     'config' => 'array',
             * ];
             *
             * Jadi tidak perlu json_encode().
             */
            $channel->config = $this->buildConfig(
                $request,
                $validated['type']
            );

            $channel->is_active = $request->has('is_active');
            $channel->save();

            return $this->redirectToSettings()
                ->with(
                    'success',
                    'Notification channel berhasil disimpan.'
                );
        } catch (\Exception $e) {
            Log::error('Create notification channel failed.', [
                'name'  => $request->input('name'),
                'type'  => $request->input('type'),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'Notification channel gagal disimpan: '
                        . $e->getMessage()
                );
        }
    }

    /**
     * Memperbarui notification channel.
     */
    public function update(Request $request, $id)
    {
        /** @var NotificationChannel $channel */
        $channel = NotificationChannel::findOrFail($id);

        $validated = $this->validateChannel(
            $request,
            true
        );

        try {
            $oldType = $channel->type;

            $channel->mailbox_id = !empty($validated['mailbox_id'])
                ? $validated['mailbox_id']
                : null;

            $channel->name = $validated['name'];
            $channel->type = $validated['type'];

            /*
             * Config lama hanya dipertahankan apabila tipe channel
             * tidak berubah.
             *
             * Contoh:
             * Telegram diubah menjadi Email.
             *
             * Config bot_token dan chat_id tidak boleh ikut masuk
             * ke config Email.
             */
            if (
                $oldType === $validated['type']
                && is_array($channel->config)
            ) {
                $oldConfig = $channel->config;
            } else {
                $oldConfig = [];
            }

            $newConfig = $this->buildConfig(
                $request,
                $validated['type']
            );

            /*
             * Field kosong tidak berada di $newConfig.
             * Karena itu token lama tetap dipertahankan apabila
             * admin tidak mengisinya kembali.
             */
            $channel->config = array_merge(
                $oldConfig,
                $newConfig
            );

            $channel->is_active = $request->has('is_active');
            $channel->save();

            return $this->redirectToSettings()
                ->with(
                    'success',
                    'Notification channel berhasil diperbarui.'
                );
        } catch (\Exception $e) {
            Log::error('Update notification channel failed.', [
                'channel_id' => $channel->id,
                'type'       => $request->input('type'),
                'error'      => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'Notification channel gagal diperbarui: '
                        . $e->getMessage()
                );
        }
    }

    /**
     * Menghapus notification channel.
     *
     * Notification rules akan ikut terhapus apabila foreign key
     * notification_rules menggunakan onDelete('cascade').
     */
    public function destroy($id)
    {
        /** @var NotificationChannel $channel */
        $channel = NotificationChannel::findOrFail($id);

        try {
            $channel->delete();

            return $this->redirectToSettings()
                ->with(
                    'success',
                    'Notification channel berhasil dihapus.'
                );
        } catch (\Exception $e) {
            Log::error('Delete notification channel failed.', [
                'channel_id' => $channel->id,
                'error'      => $e->getMessage(),
            ]);

            return $this->redirectToSettings()
                ->with(
                    'error',
                    'Notification channel gagal dihapus: '
                        . $e->getMessage()
                );
        }
    }

    /**
     * Mengaktifkan atau menonaktifkan channel.
     */
    public function toggleActive(Request $request, $id)
    {
        /** @var NotificationChannel $channel */
        $channel = NotificationChannel::findOrFail($id);

        try {
            $channel->is_active = !$channel->is_active;
            $channel->save();

            $message = $channel->is_active
                ? 'Notification channel berhasil diaktifkan.'
                : 'Notification channel berhasil dinonaktifkan.';

            if ($request->ajax()) {
                return response()->json([
                    'success'   => true,
                    'message'   => $message,
                    'is_active' => (int) $channel->is_active,

                    'status_label' => $channel->is_active
                        ? 'Active'
                        : 'Inactive',

                    'button_label' => $channel->is_active
                        ? 'Inactivate'
                        : 'Activate',

                    'button_icon' => $channel->is_active
                        ? 'glyphicon-pause'
                        : 'glyphicon-play',

                    'button_class' => $channel->is_active
                        ? 'btn-default'
                        : 'btn-success',

                    'status_class' => $channel->is_active
                        ? 'nc-status-active'
                        : 'nc-status-inactive',
                ]);
            }

            return $this->redirectToSettings()
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Toggle notification channel failed.', [
                'channel_id' => $channel->id,
                'error'      => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status channel gagal diperbarui.',
                ], 500);
            }

            return $this->redirectToSettings()
                ->with(
                    'error',
                    'Status notification channel gagal diperbarui.'
                );
        }
    }

    /**
     * Menguji pengiriman semua jenis channel.
     *
     * Sender ditentukan oleh NotificationSenderFactory melalui
     * NotificationService.
     */
    public function test($id)
    {
        /** @var NotificationChannel $channel */
        $channel = NotificationChannel::findOrFail($id);

        if (!$channel->is_active) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'Aktifkan notification channel sebelum melakukan test.'
                );
        }

        try {
            $message = "✅ Test notification channel berhasil.\n\n"
                . "Nama channel: " . $channel->name . "\n"
                . "Tipe channel: " . ucfirst($channel->type);

            $options = [
                'is_test' => true,
            ];

            if ($channel->type === 'email') {
                $options['subject'] = 'Test Notification Channel';
            }

            $result = $this->notificationService->send(
                $channel,
                null,
                $message,
                $options
            );

            /*
             * Jika sender mengembalikan false, perlakukan sebagai gagal.
             * Sender boleh juga mengembalikan array atau response object.
             */
            if ($result === false) {
                throw new \RuntimeException(
                    'Sender tidak berhasil mengirim notifikasi.'
                );
            }

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Test '
                        . ucfirst($channel->type)
                        . ' berhasil dikirim.'
                );
        } catch (\Exception $e) {
            Log::error('Notification channel test failed.', [
                'channel_id'   => $channel->id,
                'channel_type' => $channel->type,
                'error'        => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Test notification channel gagal: '
                        . $e->getMessage()
                );
        }
    }

    /**
     * Validasi data channel dan konfigurasi sesuai driver.
     */
    private function validateChannel(
        Request $request,
        $isUpdate = false
    ) {
        $supportedTypes = $this->factory->supportedTypes();

        $commonRules = [
            'mailbox_id' => [
                'nullable',
                'integer',
                'exists:mailboxes,id',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'type' => [
                'required',
                'string',
                Rule::in($supportedTypes),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];

        $type = $request->input('type');

        $configRules = in_array(
            $type,
            $supportedTypes,
            true
        )
            ? $this->factory->rules($type)
            : [];

        /*
         * Credential tidak wajib dimasukkan ulang ketika update.
         */
        if ($isUpdate) {
            $configRules = $this->makeSensitiveFieldsOptional(
                $configRules
            );
        }

        return $request->validate(
            array_merge(
                $commonRules,
                $configRules
            )
        );
    }

    /**
     * Membentuk config berdasarkan daftar field driver.
     */
    private function buildConfig(
        Request $request,
        $type
    ) {
        $rules = $this->factory->rules($type);

        $config = [];

        foreach (array_keys($rules) as $field) {
            if (!$request->exists($field)) {
                continue;
            }

            $value = $request->input($field);

            /*
             * Trim hanya untuk nilai string.
             */
            if (is_string($value)) {
                $value = trim($value);
            }

            /*
             * Jangan memasukkan nilai kosong.
             *
             * Saat update, ini mencegah token lama tertimpa
             * oleh string kosong.
             */
            if ($value === '' || $value === null) {
                continue;
            }

            $config[$field] = $value;
        }

        return $config;
    }

    /**
     * Membuat credential bersifat opsional saat update.
     */
    private function makeSensitiveFieldsOptional(
        array $rules
    ) {
        $sensitiveFields = [
            'bot_token',
            'api_token',
            'password',
            'secret',
            'webhook_secret',
        ];

        foreach ($sensitiveFields as $field) {
            if (!isset($rules[$field])) {
                continue;
            }

            $fieldRules = is_array($rules[$field])
                ? $rules[$field]
                : explode('|', $rules[$field]);

            $fieldRules = array_filter(
                $fieldRules,
                function ($rule) {
                    /*
                     * Mendukung aturan required dan required:...
                     */
                    return $rule !== 'required'
                        && strpos($rule, 'required_') !== 0;
                }
            );

            /*
             * Hindari nullable ganda.
             */
            if (!in_array('nullable', $fieldRules, true)) {
                array_unshift($fieldRules, 'nullable');
            }

            $rules[$field] = array_values($fieldRules);
        }

        return $rules;
    }

    /**
     * Menerima webhook dari platform notifikasi.
     *
     * Contoh:
     * /notification-webhook/telegram
     */
    public function webhook(Request $request, $type)
    {
        try {
            $handler = $this->webhookFactory->make($type);

            return $handler->handle($request);
        } catch (\Exception $e) {
            Log::error('Notification webhook gagal diproses.', [
                'type'  => $type,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => 'Webhook gagal diproses.',
            ], 500);
        }
    }

    /**
     * Redirect ke section Settings bawaan FreeScout.
     */
    private function redirectToSettings()
    {
        return redirect()->route('settings', [
            'section' => 'notification_channels',
        ]);
    }
}
