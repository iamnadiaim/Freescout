<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    @php
        $pageTitle = 'Lapor Poliwangi';

        $routeMailboxIdForTitle = request()->route('id') ?: request()->route('mailbox_id');

        if (!empty($routeMailboxIdForTitle) && !empty($mailbox) && !empty($mailbox->name)) {
            $pageTitle .= ' - ' . $mailbox->name;
        }
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f7f9fc;
            color: #1f2f3d;
            overflow-x: hidden;
        }

        .portal-home {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(10, 132, 223, .10), transparent 34%),
                linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);
        }

        /* =========================================================
           HERO + FORM LAYOUT
        ========================================================= */

        .hero {
            padding: 55px 24px 55px;
        }

        .hero-inner {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;

            display: grid;
            grid-template-columns: minmax(0, 1fr) 640px;
            gap: 34px;
            align-items: start;
        }

        .hero-left {
            max-width: 650px;
            padding-top: 4px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            margin-bottom: 16px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #def0ff;
            color: #0a84df;
            font-size: 13px;
            font-weight: 800;
            line-height: 1;
        }

        .hero-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0a84df;
        }

        .hero-title {
            margin: 0;
            max-width: 640px;
            font-size: 38px;
            line-height: 1.16;
            font-weight: 800;
            letter-spacing: -.4px;
            color: #102a43;
        }

        .hero-desc {
            margin: 16px 0 0;
            max-width: 650px;
            font-size: 16px;
            line-height: 1.7;
            color: #52616f;
        }

        .hero-steps {
            margin-top: 28px;
            display: grid;
            gap: 12px;
            max-width: 650px;
        }

        .hero-step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid #e6edf5;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
        }

        .hero-step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #0a84df;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 32px;
            font-size: 13px;
            font-weight: 800;
        }

        .hero-step-title {
            font-size: 14px;
            font-weight: 800;
            color: #25384b;
            margin-bottom: 3px;
        }

        .hero-step-desc {
            font-size: 13px;
            color: #7b8794;
            line-height: 1.45;
        }

        /* =========================================================
           FORM CARD
        ========================================================= */

        .submit-card {
            width: 100%;
            max-width: 640px;
            background: #ffffff;
            border: 1px solid #e6edf5;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 16px 42px rgba(15, 23, 42, .07);
        }

        .ticket-form-title {
            margin: 0 0 22px;
            color: #102a43;
            font-size: 24px;
            font-weight: 800;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 800;
            color: #25384b;
        }

        .form-control {
            width: 100%;
            min-height: 46px;
            border: 1px solid #cfd8e3;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            color: #25384b;
            outline: none;
            background: #ffffff;
        }

        .form-control:focus {
            border-color: #0a84df;
            box-shadow: 0 0 0 3px rgba(10, 132, 223, .12);
        }

        textarea.form-control {
            height: 150px;
            min-height: 150px;
            resize: vertical;
            line-height: 1.55;
        }

        select.form-control[multiple]:not(.custom-field-multiselect) {
            height: 112px;
        }

        select.custom-field-multiselect {
            display: none !important;
        }

        .lp-multiselect {
            position: relative;
            width: 100%;
        }

        .lp-multiselect-control {
            width: 100%;
            min-height: 46px;
            border: 1px solid #cfd8e3;
            border-radius: 10px;
            padding: 8px 38px 8px 10px;
            background: #ffffff;
            cursor: pointer;

            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;

            font-size: 14px;
            color: #25384b;
        }

        .lp-multiselect.open .lp-multiselect-control,
        .lp-multiselect-control:focus {
            border-color: #0a84df;
            box-shadow: 0 0 0 3px rgba(10, 132, 223, .12);
            outline: none;
        }

        .lp-multiselect-placeholder {
            color: #8a97a6;
            font-size: 14px;
        }

        .lp-multiselect-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            padding: 5px 8px;
            border-radius: 8px;
            background: #eef2f7;
            border: 1px solid #d6dee8;

            color: #25384b;
            font-size: 13px;
            line-height: 1;
        }

        .lp-multiselect-chip button {
            border: none;
            background: transparent;
            color: #6b7280;
            font-size: 15px;
            line-height: 1;
            padding: 0;
            cursor: pointer;
        }

        .lp-multiselect-chip button:hover {
            color: #d93025;
        }

        .lp-multiselect-arrow {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 18px;
            font-weight: bold;
            line-height: 1;
            pointer-events: none;
        }

        .lp-multiselect-menu {
            display: none;
            position: absolute;
            z-index: 999;
            top: calc(100% + 6px);
            left: 0;
            right: 0;

            max-height: 190px;
            overflow-y: auto;

            background: #ffffff;
            border: 1px solid #cfd8e3;
            border-radius: 10px;
            box-shadow: 0 12px 26px rgba(15, 23, 42, .12);
            padding: 6px;
        }

        .lp-multiselect.open .lp-multiselect-menu {
            display: block;
        }

        .lp-multiselect-option {
            display: flex;
            align-items: center;
            gap: 8px;

            padding: 9px 10px;
            border-radius: 8px;

            font-size: 14px;
            color: #25384b;
            cursor: pointer;
        }

        .lp-multiselect-option:hover {
            background: #eef6ff;
        }

        .lp-multiselect-option input {
            margin: 0;
        }

        #customFieldArea {
            grid-column: 1 / -1;
            display: none;
        }

        #customFieldContent {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        #customFieldContent .form-group.full {
            grid-column: 1 / -1;
        }

        .attachment-input {
            display: none;
        }

        .attachment-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px 18px;
            border: 1px dashed #b8c2cc;
            border-radius: 12px;
            background: #f8fafc;
            color: #2f3d4a;
            font-size: 13px;
            cursor: pointer;
            transition: all .18s ease;
        }

        .attachment-box:hover {
            background: #eef4ff;
            border-color: #5b8def;
            color: #2f6fd6;
        }

        .attachment-file-list {
            margin-top: 10px;
            font-size: 13px;
            color: #2f3d4a;
        }

        .attachment-file-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            margin-bottom: 6px;
            background: #f4f6f8;
            border: 1px solid #e1e5ea;
            border-radius: 8px;
        }

        .attachment-file-name {
            flex: 1;
            word-break: break-all;
        }

        .attachment-file-size {
            color: #8a97a6;
            font-size: 12px;
        }

        .checkbox-label {
            display: none;
            gap: 8px;
            align-items: flex-start;
            font-size: 14px;
            color: #2f3d4a;
            margin-bottom: 14px;
        }

        .btn-send {
            width: 100%;
            border: none;
            background: #0a84df;
            color: #ffffff;
            font-size: 16px;
            font-weight: 800;
            padding: 13px;
            border-radius: 12px;
            cursor: pointer;
            transition: background .15s ease-in-out;
            box-shadow: 0 12px 24px rgba(10, 132, 223, .20);
        }

        .btn-send:hover {
            background: #0877c8;
        }

        .alert-success {
            background: #e7f8ee;
            border: 1px solid #b8eacb;
            color: #226c3b;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert-danger {
            background: #fdecec;
            border: 1px solid #f5bcbc;
            color: #9b1c1c;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .landing-footer {
            text-align: center;
            color: #9aa6b2;
            font-size: 13px;
            padding: 26px 15px;
            border-top: 1px solid #e8edf3;
            background: #ffffff;
        }

        .anonymous-note {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
            padding: 14px 15px;
            border-radius: 14px;
            background: #fff8e6;
            border: 1px solid #ffe2a8;
            color: #5f4300;
        }

        .anonymous-icon {
            width: 26px;
            height: 26px;
            flex: 0 0 26px;
            border-radius: 50%;
            background: #f59e0b;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 16px;
            line-height: 1;
        }

        .anonymous-title {
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 3px;
        }

        .anonymous-text {
            font-size: 12px;
            line-height: 1.5;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 992px) {
            .hero {
                padding: 40px 18px 45px;
            }

            .hero-inner {
                max-width: 760px;
                grid-template-columns: 1fr;
                gap: 28px;
            }

            .hero-left {
                max-width: 100%;
            }

            .submit-card {
                max-width: 100%;
            }

            #customFieldContent {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .hero-title {
                font-size: 28px;
            }

            .hero-desc {
                font-size: 14px;
            }

            .hero-step {
                padding: 13px 14px;
            }

            .submit-card {
                padding: 20px;
                border-radius: 18px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }


        }
    </style>
</head>

<body>

    @php
        $mailboxes = isset($mailboxes) ? $mailboxes : collect([$mailbox]);

        $settingsByMailbox = isset($settingsByMailbox) ? $settingsByMailbox : collect([$mailbox->id => $setting]);

        $customFieldsByMailbox = isset($customFieldsByMailbox)
            ? $customFieldsByMailbox
            : collect([$mailbox->id => $customFields]);

        $activeMailbox = $mailbox ?: $mailboxes->first();

        $activeSetting = $setting ?: ($activeMailbox ? $settingsByMailbox->get($activeMailbox->id) : null);

        /*
         * /help       => select tetap "Pilih tujuan laporan"
         * /help/{id}  => mailbox sesuai ID otomatis terpilih
         * validasi gagal => pakai old('mailbox_id')
         */
        $routeMailboxId = request()->route('id') ?: request()->route('mailbox_id');

        $activeMailboxId = old('mailbox_id', $routeMailboxId);

        /*
         * Kalau belum pilih mailbox, form action jangan diarahkan ke mailbox pertama.
         */
        $selectedMailboxForAction = $activeMailboxId ? $mailboxes->firstWhere('id', (int) $activeMailboxId) : null;

        $defaultAction = $selectedMailboxForAction
            ? route('laporpoliwangi.end_user_portal.submit', $selectedMailboxForAction->id)
            : '#';

        $loggedEmail = $email ?? session('end_user_portal_email');

        $loggedCustomer = isset($loggedCustomer) ? $loggedCustomer : null;

        $loggedName = '';

        if (!empty($loggedCustomer)) {
            $loggedName = trim((string) $loggedCustomer->first_name . ' ' . (string) $loggedCustomer->last_name);
        }
    @endphp

    <div class="portal-home">

        {{-- NAVBAR TERPISAH --}}
        @include('laporpoliwangi::end_user_portal.partials.navbar', [
            'mailbox' => $activeMailbox,
            'email' => $loggedEmail,
        ])

        {{-- HERO + FORM SUBMIT --}}
        <section class="hero" id="submit-ticket">
            <div class="hero-inner">

                {{-- KIRI: LANDING CONTENT --}}
                <div class="hero-left">
                    <div class="hero-badge">
                        <span class="hero-badge-dot"></span>
                        Portal Layanan Pelaporan dan Bantuan
                    </div>

                    <h1 class="hero-title">
                        Pusat Layanan Pelaporan dan Bantuan Poliwangi
                    </h1>

                    <p class="hero-desc">
                        Laporkan keluhan, sampaikan saran, atau ajukan permintaan bantuan kepada unit layanan yang
                        tepat.
                        Pilih tujuan laporan agar tiket dapat diteruskan kepada petugas terkait.
                    </p>

                    <div class="hero-steps">
                        <div class="hero-step">
                            <div class="hero-step-number">1</div>
                            <div>
                                <div class="hero-step-title">Isi Identitas Jika Diperlukan</div>
                                <div class="hero-step-desc">
                                    Nama dan email bersifat opsional. Kosongkan jika ingin laporan tercatat sebagai
                                    anonim.
                                </div>
                            </div>
                        </div>

                        <div class="hero-step">
                            <div class="hero-step-number">2</div>
                            <div>
                                <div class="hero-step-title">Pilih Mailbox</div>
                                <div class="hero-step-desc">
                                    Tentukan unit tujuan laporan.
                                </div>
                            </div>
                        </div>

                        <div class="hero-step">
                            <div class="hero-step-number">3</div>
                            <div>
                                <div class="hero-step-title">Kirim Laporan</div>
                                <div class="hero-step-desc">
                                    Custom field tampil sesuai mailbox.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KANAN: FORM --}}
                <div class="submit-card">
                    <h2 class="ticket-form-title">
                        {{ $activeSetting ? $activeSetting->submit_ticket_title : 'Submit a Ticket' }}
                    </h2>

                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>Terjadi kesalahan.</strong>
                            <ul style="margin-bottom: 0;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="landingTicketForm" action="{{ $defaultAction }}" method="POST"
                        enctype="multipart/form-data">

                        {{ csrf_field() }}

                        <input type="hidden" name="subject" id="hiddenSubject"
                            value="{{ old('subject', 'New Ticket from End-User Portal') }}">

                        <div class="anonymous-note">
                            <div class="anonymous-icon">!</div>
                            <div>
                                <div class="anonymous-title">Laporan dapat dikirim tanpa identitas</div>
                                <div class="anonymous-text">
                                    Nama dan email boleh dikosongkan. Jika tidak diisi, laporan akan otomatis tercatat
                                    sebagai
                                    <strong>Pelapor Anonim</strong>.
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            {{-- NAMA --}}
                            <div class="form-group">
                                <label class="form-label">
                                    Nama
                                    <span style="color:#9ca3af; font-weight:600;">(opsional)</span>
                                </label>

                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $loggedName) ?? '' }}" placeholder="Masukkan nama"
                                    {{ !empty($loggedEmail) ? 'readonly' : '' }}>
                            </div>

                            {{-- EMAIL --}}
                            <div class="form-group">
                                <label class="form-label">
                                    Email
                                    <span style="color:#9ca3af; font-weight:600;">(opsional)</span>
                                </label>

                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', $loggedEmail) ?? '' }}" placeholder="Masukkan email"
                                    {{ !empty($loggedEmail) ? 'readonly' : '' }}>
                            </div>

                            {{-- MAILBOX --}}
                            <div class="form-group full">
                                <label class="form-label">Unit Tujuan</label>

                                <select name="mailbox_id" id="mailboxSelect" class="form-control" required>
                                    <option value="" data-page-title="Lapor Poliwangi"
                                        {{ empty($activeMailboxId) ? 'selected' : '' }}>
                                        Pilih tujuan laporan
                                    </option>

                                    @foreach ($mailboxes as $mailboxItem)
                                        @php
                                            $settingItem = $settingsByMailbox->get($mailboxItem->id);
                                        @endphp

                                        <option value="{{ $mailboxItem->id }}"
                                            data-action="{{ route('laporpoliwangi.end_user_portal.submit', $mailboxItem->id) }}"
                                            data-url="{{ route('laporpoliwangi.end_user_portal.submit_ticket', $mailboxItem->id) }}"
                                            data-mailbox-name="{{ $mailboxItem->name }}"
                                            data-page-title="Lapor Poliwangi - {{ $mailboxItem->name }}"
                                            data-subject="{{ !empty($settingItem) && $settingItem->subject_field ? '1' : '0' }}"
                                            data-consent="{{ !empty($settingItem) && $settingItem->consent_checkbox ? '1' : '0' }}"
                                            {{ (string) $activeMailboxId === (string) $mailboxItem->id ? 'selected' : '' }}>
                                            {{ $mailboxItem->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- SUBJECT DINAMIS --}}
                            <div class="form-group full" id="subjectFieldWrap" style="display:none;">
                                <label class="form-label">Subject</label>
                                <input type="text" id="subjectInput" class="form-control"
                                    value="{{ old('') ?? '' }}" placeholder="Subject">
                            </div>

                            {{-- CUSTOM FIELD --}}
                            <div id="customFieldArea" style="display:none; grid-column: 1 / -1;">
                                <div id="customFieldContent"></div>
                            </div>

                            {{-- PESAN --}}
                            <div class="form-group full">
                                <label class="form-label">Pesan</label>
                                <textarea name="message" class="form-control" placeholder="Tulis pesan atau laporan" required>{{ old('') ?? '' }}</textarea>
                            </div>

                            {{-- ATTACHMENT --}}
                            <div class="form-group full">
                                <label class="form-label">Lampiran</label>

                                <label class="attachment-box" for="attachments">
                                    <span class="attachment-icon">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"
                                            aria-hidden="true">
                                            <path
                                                d="M16.5 6.5v10.25c0 2.48-2.02 4.5-4.5 4.5s-4.5-2.02-4.5-4.5V5.5c0-1.52 1.23-2.75 2.75-2.75S13 3.98 13 5.5v10.75c0 .55-.45 1-1 1s-1-.45-1-1V6.5H9.5v9.75c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V5.5c0-2.35-1.9-4.25-4.25-4.25S6 3.15 6 5.5v11.25c0 3.31 2.69 6 6 6s6-2.69 6-6V6.5h-1.5z" />
                                        </svg>
                                    </span>
                                    <span>Tambah lampiran jika diperlukan</span>
                                </label>

                                <input type="file" name="attachments[]" id="attachments" class="attachment-input"
                                    multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">

                                <div id="attachment_file_list" class="attachment-file-list"></div>
                            </div>
                        </div>

                        {{-- CONSENT DINAMIS --}}
                        <label class="checkbox-label" id="consentBox">
                            <input type="checkbox" name="consent" value="1" id="consentInput">
                            <span>Saya menyetujui pengiriman laporan ini.</span>
                        </label>

                        <button type="submit" class="btn-send" {{ !$mailboxes->count() ? 'disabled' : '' }}>
                            Kirim Laporan
                        </button>
                    </form>

                    {{-- TEMPLATE CUSTOM FIELD PER MAILBOX --}}
                    @foreach ($mailboxes as $mailboxItem)
                        <script type="text/template" id="custom-template-{{ $mailboxItem->id }}">
                        @php
                            $fields = $customFieldsByMailbox->get($mailboxItem->id, collect());
                        @endphp

                        @foreach ($fields as $field)
                            <div class="form-group full">
                                <label class="form-label">
                                    {{ $field->nama_field }}
                                    @if ($field->required)
                                        <span style="color:#d93025;">*</span>
                                    @endif
                                </label>

                                @if ($field->type_field == 'textarea')
                                    <textarea name="custom_fields[{{ $field->id }}]"
                                              class="form-control"
                                              placeholder="{{ $field->nama_field }}"
                                              {{ $field->required ? 'required' : '' }}>{{ old('custom_fields.' . $field->id) ?? '' }}</textarea>

                                @elseif ($field->type_field == 'number')
                                    <input type="number"
                                           name="custom_fields[{{ $field->id }}]"
                                           class="form-control"
                                           value="{{ old('custom_fields.' . $field->id) ?? '' }}"
                                           placeholder="{{ $field->nama_field }}"
                                           {{ $field->required ? 'required' : '' }}>

                                @elseif ($field->type_field == 'date')
                                    <input type="date"
                                           name="custom_fields[{{ $field->id }}]"
                                           class="form-control"
                                           value="{{ old('custom_fields.' . $field->id) ?? '' }}"
                                           {{ $field->required ? 'required' : '' }}>

                                @elseif ($field->type_field == 'dropdown')
                                    @php
                                        $options = is_array($field->options)
                                            ? $field->options
                                            : json_decode($field->options, true);

                                        $options = $options ?: [];
                                    @endphp

                                    <select name="custom_fields[{{ $field->id }}]"
                                            class="form-control"
                                            {{ $field->required ? 'required' : '' }}>
                                        <option value="">Pilih {{ $field->nama_field }}</option>

                                        @foreach ($options as $option)
                                            <option value="{{ $option }}"
                                                    {{ old('custom_fields.' . $field->id) == $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>

                                @elseif ($field->type_field == 'multiselect')
                                    @php
                                        $options = is_array($field->options)
                                            ? $field->options
                                            : json_decode($field->options, true);

                                        $options = $options ?: [];
                                        $oldValues = old('custom_fields.' . $field->id, []);
                                    @endphp

                                    <select name="custom_fields[{{ $field->id }}][]"
        class="form-control custom-field-multiselect"
        multiple
        data-placeholder="Pilih {{ $field->nama_field }}"
        {{ $field->required ? 'required' : '' }}>
                                        @foreach ($options as $option)
                                            <option value="{{ $option }}"
                                                    {{ in_array($option, $oldValues) ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>

                                @else
                                    <input type="text"
                                           name="custom_fields[{{ $field->id }}]"
                                           class="form-control"
                                           value="{{ old('custom_fields.' . $field->id) ?? '' }}"
                                           placeholder="{{ $field->nama_field }}"
                                           {{ $field->required ? 'required' : '' }}>
                                @endif
                            </div>
                        @endforeach
                    </script>
                    @endforeach
                </div>
            </div>
        </section>

        @include('laporpoliwangi::end_user_portal.partials.footer', [
            'mailbox' => $activeMailbox,
            'setting' => $activeSetting,
        ])

        {{-- TEMPLATE FOOTER DEFAULT --}}
        <script type="text/template" id="footer-template-default">
    © {{ date('Y') }} Lapor Poliwangi
</script>

        {{-- TEMPLATE FOOTER PER MAILBOX --}}
        @foreach ($mailboxes as $mailboxItem)
            @php
                $settingItem = $settingsByMailbox->get($mailboxItem->id);

                if (!empty($settingItem) && !empty($settingItem->footer)) {
                    $footerTextItem = $settingItem->footer;
                } else {
                    $footerTextItem = '© {%year%} {%mailbox.name%}';
                }

                $footerTextItem = str_replace('{%year%}', date('Y'), $footerTextItem);
                $footerTextItem = str_replace('{%mailbox.name%}', $mailboxItem->name, $footerTextItem);
            @endphp

            <script type="text/template" id="footer-template-{{ $mailboxItem->id }}">
        {!! $footerTextItem !!}
    </script>
        @endforeach
    </div>

    <script>
        var mailboxSelect = document.getElementById('mailboxSelect');
        var form = document.getElementById('landingTicketForm');

        var customFieldArea = document.getElementById('customFieldArea');
        var customFieldContent = document.getElementById('customFieldContent');

        var subjectFieldWrap = document.getElementById('subjectFieldWrap');
        var subjectInput = document.getElementById('subjectInput');
        var hiddenSubject = document.getElementById('hiddenSubject');

        var defaultHelpUrl = "{{ url('/help') }}";

        var consentBox = document.getElementById('consentBox');
        var consentInput = document.getElementById('consentInput');

        var portalFooterText = document.getElementById('portalFooterText');

        function updateFooter(mailboxId) {
            if (!portalFooterText) {
                return;
            }

            var footerTemplate = null;

            if (mailboxId) {
                footerTemplate = document.getElementById('footer-template-' + mailboxId);
            } else {
                footerTemplate = document.getElementById('footer-template-default');
            }

            if (footerTemplate) {
                portalFooterText.innerHTML = footerTemplate.innerHTML.trim();
            }
        }

        function refreshMailboxState(updateUrl) {
            if (!mailboxSelect) {
                return;
            }

            var selectedOption = mailboxSelect.options[mailboxSelect.selectedIndex];
            var mailboxId = mailboxSelect.value;

            customFieldContent.innerHTML = '';
            customFieldArea.style.display = 'none';

            /*
             * Kalau user pilih default: "Pilih tujuan laporan"
             */
            if (!mailboxId || !selectedOption) {
                document.title = selectedOption && selectedOption.getAttribute('data-page-title') ?
                    selectedOption.getAttribute('data-page-title') :
                    'Lapor Poliwangi';

                updateFooter(null);

                if (updateUrl) {
                    window.history.replaceState({}, '', defaultHelpUrl);
                }

                form.action = '#';

                subjectFieldWrap.style.display = 'none';
                subjectInput.removeAttribute('name');
                subjectInput.required = false;

                hiddenSubject.setAttribute('name', 'subject');
                hiddenSubject.value = 'New Ticket from End-User Portal';

                consentBox.style.display = 'none';
                consentInput.required = false;
                consentInput.checked = false;

                return;
            }

            /*
             * Kalau user pilih mailbox tertentu.
             */
            if (selectedOption.getAttribute('data-action')) {
                form.action = selectedOption.getAttribute('data-action');
            }

            if (selectedOption.getAttribute('data-page-title')) {
                document.title = selectedOption.getAttribute('data-page-title');
            }

            updateFooter(mailboxId);

            if (updateUrl && selectedOption.getAttribute('data-url')) {
                window.history.replaceState({}, '', selectedOption.getAttribute('data-url'));
            }

            var template = document.getElementById('custom-template-' + mailboxId);

            if (template && template.innerHTML.trim() !== '') {
                customFieldContent.innerHTML = template.innerHTML;
                customFieldArea.style.display = 'block';

                initPortalMultiselects();
            }

            var useSubject = selectedOption.getAttribute('data-subject') === '1';

            if (useSubject) {
                subjectFieldWrap.style.display = 'block';
                subjectInput.setAttribute('name', 'subject');
                subjectInput.required = true;
                hiddenSubject.removeAttribute('name');
            } else {
                subjectFieldWrap.style.display = 'none';
                subjectInput.removeAttribute('name');
                subjectInput.required = false;

                hiddenSubject.setAttribute('name', 'subject');
                hiddenSubject.value = 'Laporan untuk ' + selectedOption.getAttribute('data-mailbox-name');
            }

            var useConsent = selectedOption.getAttribute('data-consent') === '1';

            if (useConsent) {
                consentBox.style.display = 'flex';
                consentInput.required = true;
            } else {
                consentBox.style.display = 'none';
                consentInput.required = false;
                consentInput.checked = false;
            }
        }

        function initPortalMultiselects() {
            var selects = document.querySelectorAll('select.custom-field-multiselect');

            selects.forEach(function(select) {
                if (select.dataset.multiselectReady === '1') {
                    return;
                }

                select.dataset.multiselectReady = '1';

                var wrapper = document.createElement('div');
                wrapper.className = 'lp-multiselect';

                var control = document.createElement('div');
                control.className = 'lp-multiselect-control';
                control.setAttribute('tabindex', '0');

                var arrow = document.createElement('span');
                arrow.className = 'lp-multiselect-arrow';
                arrow.innerHTML = '▾';

                var menu = document.createElement('div');
                menu.className = 'lp-multiselect-menu';

                wrapper.appendChild(control);
                wrapper.appendChild(arrow);
                wrapper.appendChild(menu);

                select.parentNode.insertBefore(wrapper, select.nextSibling);

                function renderControl() {
                    control.innerHTML = '';

                    var selectedOptions = Array.prototype.slice.call(select.options).filter(function(option) {
                        return option.selected;
                    });

                    if (!selectedOptions.length) {
                        var placeholder = document.createElement('span');
                        placeholder.className = 'lp-multiselect-placeholder';
                        placeholder.innerHTML = select.getAttribute('data-placeholder') || 'Pilih opsi';
                        control.appendChild(placeholder);
                        return;
                    }

                    selectedOptions.forEach(function(option) {
                        var chip = document.createElement('span');
                        chip.className = 'lp-multiselect-chip';

                        var text = document.createElement('span');
                        text.innerHTML = option.text;

                        var remove = document.createElement('button');
                        remove.type = 'button';
                        remove.innerHTML = '×';

                        remove.addEventListener('click', function(e) {
                            e.stopPropagation();
                            option.selected = false;
                            renderControl();
                            renderMenu();
                        });

                        chip.appendChild(text);
                        chip.appendChild(remove);
                        control.appendChild(chip);
                    });
                }

                function renderMenu() {
                    menu.innerHTML = '';

                    Array.prototype.slice.call(select.options).forEach(function(option) {
                        var label = document.createElement('label');
                        label.className = 'lp-multiselect-option';

                        var checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.checked = option.selected;

                        checkbox.addEventListener('change', function() {
                            option.selected = checkbox.checked;
                            renderControl();
                            renderMenu();
                        });

                        var text = document.createElement('span');
                        text.innerHTML = option.text;

                        label.appendChild(checkbox);
                        label.appendChild(text);
                        menu.appendChild(label);
                    });
                }

                control.addEventListener('click', function(e) {
                    e.stopPropagation();
                    wrapper.classList.toggle('open');
                });

                control.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        wrapper.classList.toggle('open');
                    }
                });

                document.addEventListener('click', function() {
                    wrapper.classList.remove('open');
                });

                menu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });

                renderControl();
                renderMenu();
            });
        }

        if (mailboxSelect) {
            mailboxSelect.addEventListener('change', function() {
                refreshMailboxState(true);
            });

            refreshMailboxState(false);
            initPortalMultiselects();
        }

        var attachments = document.getElementById('attachments');

        function formatFileSize(bytes) {
            if (bytes === 0) {
                return '0 KB';
            }

            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));

            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        if (attachments) {
            attachments.addEventListener('change', function() {
                var list = document.getElementById('attachment_file_list');
                var files = this.files;
                var html = '';

                if (files.length > 0) {
                    for (var i = 0; i < files.length; i++) {
                        html += '<div class="attachment-file-item">';
                        html += '<span>📄</span>';
                        html += '<span class="attachment-file-name">' + files[i].name + '</span>';
                        html += '<span class="attachment-file-size">' + formatFileSize(files[i].size) + '</span>';
                        html += '</div>';
                    }
                }

                list.innerHTML = html;
            });
        }
    </script>

</body>

</html>



