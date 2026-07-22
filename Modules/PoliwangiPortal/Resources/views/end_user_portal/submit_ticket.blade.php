<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    @php
        $pageTitle = 'Poliwangi Portal';

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

        .report-type-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }

        .report-type-card {
            border: 2px solid #e1e5ea;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s;
            background: #ffffff;
            position: relative;
        }

        .report-type-card:hover {
            border-color: #b3c5df;
            background: #f8fafc;
        }

        .report-type-card.active {
            border-color: #0a84df;
            background: #f0f7ff;
            box-shadow: 0 4px 12px rgba(10, 132, 223, 0.1);
        }

        .report-type-radio {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .report-type-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
        }

        .report-type-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #b3c5df;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .report-type-card.active .report-type-icon {
            border-color: #0a84df;
        }

        .report-type-icon::after {
            content: '';
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #0a84df;
            transform: scale(0);
            transition: transform 0.2s;
        }

        .report-type-card.active .report-type-icon::after {
            transform: scale(1);
        }

        .report-type-title {
            font-size: 15px;
            font-weight: 800;
            color: #102a43;
        }

        .report-type-desc {
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
            padding-left: 30px;
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
            ? route('PoliwangiPortal.end_user_portal.submit', $selectedMailboxForAction->id)
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
        @include('poliwangiportal::end_user_portal.partials.navbar', [
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
                                <div class="hero-step-title">Isi Identitas Anda</div>
                                <div class="hero-step-desc">
                                    Nama wajib diisi untuk Pelaporan Terbuka. Jika ingin melapor secara anonim, silakan pilih opsi Pelaporan Anonim.
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
                        <div id="successModalOverlay" class="success-modal-overlay">
                            <div class="success-modal-box">
                                <div class="success-icon">
                                    <svg class="checkmark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                                <h3 class="success-title">Laporan Terkirim!</h3>
                                <p class="success-desc">Terima kasih, laporan Anda telah kami terima.</p>
                                
                                @if (session('secret_tracking_code'))
                                    <div class="ticket-number-box" style="background: #fff5eb; border: 1px solid #ffcc80;">
                                        <span class="ticket-label" style="color: #e65100; font-weight: bold;">SIMPAN KODE PELACAK RAHASIA INI</span>
                                        <div class="ticket-number-flex">
                                            <span class="ticket-number" id="ticketNumberText" style="color: #e65100;">{{ session('secret_tracking_code') }}</span>
                                            <button class="btn-copy-ticket" onclick="copyTicketNumber()" title="Salin Kode Pelacak">
                                                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="ticket-note" style="color: #d84315; font-weight: bold;">Anda sedang melapor secara Anonim. Anda WAJIB menyimpan Kode Pelacak di atas untuk mengecek status dan balasan dari Admin.</p>
                                @elseif (session('secret_tracking_code_terbuka'))
                                    <div class="ticket-number-box">
                                        <span class="ticket-label">Nomor Tiket Anda</span>
                                        <div class="ticket-number-flex">
                                            <span class="ticket-number" id="ticketNumberText">#{{ session('ticket_number') }}</span>
                                            <button class="btn-copy-ticket" onclick="copyTicketNumber()" title="Salin Nomor Tiket">
                                                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div style="background: #eef2f7; border: 1px solid #cfd8e3; border-radius: 12px; padding: 12px; margin-bottom: 16px;">
                                        <span style="display: block; font-size: 12px; color: #25384b; font-weight: bold; margin-bottom: 6px; text-transform: uppercase;">Kode Akses Pelacak</span>
                                        <div style="display: flex; justify-content: center; align-items: center; gap: 8px;">
                                            <span style="font-size: 20px; font-weight: 900; color: #0a84df;" id="trackingCodeText">{{ session('secret_tracking_code_terbuka') }}</span>
                                            <button style="background: transparent; border: none; color: #0a84df; cursor: pointer;" onclick="navigator.clipboard.writeText('{{ session('secret_tracking_code_terbuka') }}'); alert('Kode Akses tersalin!');" title="Salin Kode Akses">
                                                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <p class="ticket-note" style="color: #25384b;">Karena Anda tidak mengisi Email, Anda WAJIB menyimpan <strong>Kode Akses Pelacak</strong> di atas untuk mengecek status dan balasan dari Admin di menu Cek Status.</p>
                                @elseif (session('ticket_number'))
                                    <div class="ticket-number-box">
                                        <span class="ticket-label">Nomor Tiket Anda</span>
                                        <div class="ticket-number-flex">
                                            <span class="ticket-number" id="ticketNumberText">#{{ session('ticket_number') }}</span>
                                            <button class="btn-copy-ticket" onclick="copyTicketNumber()" title="Salin Nomor Tiket">
                                                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="ticket-note">Harap simpan nomor tiket ini untuk melacak status laporan Anda di menu <strong>Cek Status</strong>.</p>
                                @endif
                                
                                <button type="button" class="btn-close-modal" onclick="closeSuccessModal()">Tutup</button>
                            </div>
                        </div>
                        
                        <style>
                            .success-modal-overlay {
                                position: fixed;
                                top: 0; left: 0; right: 0; bottom: 0;
                                background: rgba(15, 23, 42, 0.6);
                                backdrop-filter: blur(4px);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                z-index: 999999;
                                animation: fadeIn 0.3s ease-out forwards;
                            }
                            
                            .success-modal-box {
                                background: #ffffff;
                                border-radius: 24px;
                                padding: 40px 32px;
                                max-width: 420px;
                                width: 90%;
                                text-align: center;
                                box-shadow: 0 20px 48px rgba(0, 0, 0, 0.15);
                                transform: scale(0.9);
                                opacity: 0;
                                animation: popUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.1s forwards;
                            }
                            
                            .success-icon {
                                width: 80px;
                                height: 80px;
                                background: linear-gradient(135deg, #34d399, #059669);
                                color: #ffffff;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin: 0 auto 24px;
                                box-shadow: 
                                    inset -4px -4px 8px rgba(0,0,0,0.15),
                                    inset 4px 4px 8px rgba(255,255,255,0.4),
                                    0 10px 20px rgba(16, 185, 129, 0.3);
                                transform-style: preserve-3d;
                            }
                            
                            .success-icon svg {
                                width: 44px;
                                height: 44px;
                            }
                            
                            .checkmark {
                                transform: scale(0) translateZ(20px);
                                transform-origin: center;
                                animation: bubblePop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.3s forwards;
                                filter: drop-shadow(2px 4px 6px rgba(0, 0, 0, 0.2));
                            }
                            
                            @keyframes bubblePop {
                                0% { transform: scale(0) translateZ(0); opacity: 0; }
                                60% { transform: scale(1.15) translateZ(25px); opacity: 1; }
                                100% { transform: scale(1) translateZ(20px); opacity: 1; }
                            }
                            
                            .success-title {
                                font-size: 24px;
                                font-weight: 800;
                                color: #1e293b;
                                margin: 0 0 8px;
                            }
                            
                            .success-desc {
                                font-size: 15px;
                                color: #64748b;
                                margin: 0 0 24px;
                            }
                            
                            .ticket-number-box {
                                background: #f8fafc;
                                border: 2px dashed #cbd5e1;
                                border-radius: 12px;
                                padding: 16px;
                                margin-bottom: 16px;
                            }
                            
                            .ticket-label {
                                display: block;
                                font-size: 13px;
                                font-weight: 600;
                                color: #64748b;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                                margin-bottom: 8px;
                            }

                            .ticket-number-flex {
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                gap: 12px;
                            }
                            
                            .ticket-number {
                                font-size: 32px;
                                font-weight: 900;
                                color: #0a84df;
                                letter-spacing: 1px;
                            }

                            .btn-copy-ticket {
                                background: #e0f2fe;
                                color: #0284c7;
                                border: none;
                                border-radius: 8px;
                                width: 40px;
                                height: 40px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                cursor: pointer;
                                transition: all 0.2s;
                            }

                            .btn-copy-ticket:hover {
                                background: #bae6fd;
                                color: #0369a1;
                            }
                            
                            .ticket-note {
                                font-size: 13px;
                                color: #64748b;
                                line-height: 1.5;
                                margin: 0 0 28px;
                            }
                            
                            .btn-close-modal {
                                background: #0a84df;
                                color: #ffffff;
                                border: none;
                                padding: 14px 32px;
                                font-size: 16px;
                                font-weight: 700;
                                border-radius: 12px;
                                cursor: pointer;
                                width: 100%;
                                transition: background 0.2s;
                            }
                            
                            .btn-close-modal:hover {
                                background: #0875cf;
                            }
                            
                            @keyframes fadeIn {
                                from { opacity: 0; }
                                to { opacity: 1; }
                            }
                            
                            @keyframes popUp {
                                from { transform: scale(0.9); opacity: 0; }
                                to { transform: scale(1); opacity: 1; }
                            }
                        </style>
                        
                        <script {!! \Helper::cspNonceAttr() !!}>
                            function closeSuccessModal() {
                                const overlay = document.getElementById('successModalOverlay');
                                if(overlay) {
                                    overlay.style.display = 'none';
                                }
                            }

                            function copyTicketNumber() {
                                const textElement = document.getElementById('ticketNumberText');
                                if (!textElement) return;
                                
                                let text = textElement.innerText.trim();
                                if (text.startsWith('#')) {
                                    text = text.substring(1);
                                }
                                
                                navigator.clipboard.writeText(text).then(() => {
                                    alert('Berhasil disalin!');
                                }).catch(err => {
                                    console.error('Gagal menyalin teks: ', err);
                                });
                            }

                            // Save to local storage for anonymous tracking history
                            document.addEventListener('DOMContentLoaded', function() {
                                @if (session('secret_tracking_code'))
                                    try {
                                        const newTicket = {
                                            number: '{{ session("secret_tracking_code") }}',
                                            subject: @json(session("ticket_subject", "Laporan Anonim")),
                                            date: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
                                        };
                                        
                                        let history = JSON.parse(localStorage.getItem('anonymous_tickets_history')) || [];
                                        
                                        // Remove if exists to prevent duplicates, then add to front
                                        history = history.filter(t => t.number !== newTicket.number);
                                        history.unshift(newTicket);
                                        
                                        // Keep only last 10 tickets
                                        if (history.length > 10) {
                                            history = history.slice(0, 10);
                                        }
                                        
                                        localStorage.setItem('anonymous_tickets_history', JSON.stringify(history));
                                    } catch (e) {
                                        console.error('Failed to save ticket to local storage', e);
                                    }
                                @elseif (session('secret_tracking_code_terbuka'))
                                    try {
                                        const newTicket = {
                                            number: '{{ session("secret_tracking_code_terbuka") }}',
                                            subject: @json(session("ticket_subject", "Laporan Tanpa Email")),
                                            date: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
                                        };
                                        
                                        let history = JSON.parse(localStorage.getItem('anonymous_tickets_history')) || [];
                                        
                                        history = history.filter(t => t.number !== newTicket.number);
                                        history.unshift(newTicket);
                                        
                                        if (history.length > 10) {
                                            history = history.slice(0, 10);
                                        }
                                        
                                        localStorage.setItem('anonymous_tickets_history', JSON.stringify(history));
                                    } catch (e) {
                                        console.error('Failed to save ticket to local storage', e);
                                    }
                                @elseif (session('ticket_number'))
                                    try {
                                        const newTicket = {
                                            number: '{{ session("ticket_number") }}',
                                            subject: @json(session("ticket_subject", "Laporan Baru")),
                                            date: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
                                        };
                                        
                                        let history = JSON.parse(localStorage.getItem('anonymous_tickets_history')) || [];
                                        
                                        history = history.filter(t => t.number !== newTicket.number);
                                        history.unshift(newTicket);
                                        
                                        if (history.length > 10) {
                                            history = history.slice(0, 10);
                                        }
                                        
                                        localStorage.setItem('anonymous_tickets_history', JSON.stringify(history));
                                    } catch (e) {
                                        console.error('Failed to save ticket to local storage', e);
                                    }
                                @endif
                            });
                        </script>
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

                        <div class="report-type-options">
                            <label class="report-type-card active" id="cardTerbuka">
                                <input type="radio" name="report_type" value="terbuka" class="report-type-radio" checked onchange="toggleReportType()">
                                <div class="report-type-header">
                                    <div class="report-type-icon"></div>
                                    <div class="report-type-title">Pelaporan Terbuka</div>
                                </div>
                                <div class="report-type-desc">Nama dan Email wajib disertakan.</div>
                            </label>

                            <label class="report-type-card" id="cardAnonim">
                                <input type="radio" name="report_type" value="anonim" class="report-type-radio" onchange="toggleReportType()">
                                <div class="report-type-header">
                                    <div class="report-type-icon"></div>
                                    <div class="report-type-title">Pelaporan Anonim</div>
                                </div>
                                <div class="report-type-desc">Identitas Anda akan disembunyikan sepenuhnya.</div>
                            </label>
                        </div>

                        <div class="form-grid">
                            {{-- NAMA --}}
                            <div class="form-group" id="groupName">
                                <label class="form-label">
                                    Nama
                                </label>

                                <input type="text" name="name" id="inputName" class="form-control"
                                    value="{{ old('name', $loggedName) ?? '' }}" placeholder="Masukkan nama"
                                    {{ !empty($loggedEmail) ? 'readonly' : '' }}>
                            </div>

                            {{-- EMAIL --}}
                            <div class="form-group" id="groupEmail">
                                <label class="form-label">
                                    Email
                                </label>

                                <input type="email" name="email" id="inputEmail" class="form-control"
                                    value="{{ old('email', $loggedEmail) ?? '' }}" placeholder="Masukkan email"
                                    {{ !empty($loggedEmail) ? 'readonly' : '' }}>
                            </div>

                            {{-- MAILBOX --}}
                            <div class="form-group full">
                                <label class="form-label">Unit Tujuan</label>

                                <select name="mailbox_id" id="mailboxSelect" class="form-control" required>
                                    <option value="" data-page-title="Poliwangi Portal"
                                        {{ empty($activeMailboxId) ? 'selected' : '' }}>
                                        Pilih tujuan laporan
                                    </option>

                                    @foreach ($mailboxes as $mailboxItem)
                                        @php
                                            $settingItem = $settingsByMailbox->get($mailboxItem->id);
                                        @endphp

                                        <option value="{{ $mailboxItem->id }}"
                                            data-action="{{ route('PoliwangiPortal.end_user_portal.submit', $mailboxItem->id) }}"
                                            data-url="{{ route('PoliwangiPortal.end_user_portal.submit_ticket', $mailboxItem->id) }}"
                                            data-mailbox-name="{{ $mailboxItem->name }}"
                                            data-page-title="Poliwangi Portal - {{ $mailboxItem->name }}"
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
                                    value="{{ old('subject') ?? '' }}" placeholder="Subject">
                            </div>

                            {{-- CUSTOM FIELD --}}
                            <div id="customFieldArea" style="display:none; grid-column: 1 / -1;">
                                <div id="customFieldContent"></div>
                            </div>

                            {{-- PESAN --}}
                            <div class="form-group full">
                                <label class="form-label">Pesan</label>
                                <textarea name="message" class="form-control" placeholder="Tulis pesan atau laporan" required>{{ old('message') ?? '' }}</textarea>
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

                        @if(empty($loggedEmail))
                        <div class="form-group full" style="margin-top: 15px;">
                            <label class="form-label">Keamanan: Silakan ketik ulang teks pada gambar di bawah</label>
                            <div style="margin-bottom: 10px;">
                                {!! captcha_img('flat') !!}
                            </div>
                            <input type="text"
                                   name="captcha"
                                   class="form-control"
                                   placeholder="Masukkan kode captcha"
                                   required>
                        </div>
                        @endif

                        <button type="submit" class="btn-send" {{ !$mailboxes->count() ? 'disabled' : '' }}>
                            Kirim Laporan
                        </button>
                    </form>

                    {{-- TEMPLATE CUSTOM FIELD PER MAILBOX --}}
                    @foreach ($mailboxes as $mailboxItem)
                        <script type="text/template" id="custom-template-{{ $mailboxItem->id }}">
                            @action('portal.ticket.form_bottom', $mailboxItem, $settingsByMailbox->get($mailboxItem->id))
                        </script>
                    @endforeach
                </div>
            </div>
        </section>

        @include('poliwangiportal::end_user_portal.partials.footer', [
            'mailbox' => $activeMailbox,
            'setting' => $activeSetting,
        ])

        {{-- TEMPLATE FOOTER DEFAULT --}}
        <script type="text/template" id="footer-template-default">
    © {{ date('Y') }} Poliwangi Portal
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
        {{ $footerTextItem }}
    </script>
        @endforeach
    </div>

    <script {!! \Helper::cspNonceAttr() !!}>
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

        function updateRequiredAsterisks() {
            document.querySelectorAll('.required-asterisk').forEach(function(el) {
                el.remove();
            });

            var requiredElements = document.querySelectorAll('input[required], select[required], textarea[required]');
            requiredElements.forEach(function(el) {
                var label = null;
                
                if (el.parentElement && el.parentElement.tagName === 'LABEL') {
                    label = el.parentElement;
                } else if (el.previousElementSibling && el.previousElementSibling.tagName === 'LABEL') {
                    label = el.previousElementSibling;
                } else {
                    var wrapper = el.closest('.form-group, .margin-bottom, .custom-field');
                    if (wrapper) {
                        label = wrapper.querySelector('label');
                    }
                }

                if (label && !label.querySelector('.required-asterisk')) {
                    var asterisk = document.createElement('span');
                    asterisk.className = 'required-asterisk';
                    asterisk.style.color = '#e11d48';
                    asterisk.style.marginLeft = '4px';
                    asterisk.innerHTML = '*';
                    label.appendChild(asterisk);
                }
            });
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
                    'Poliwangi Portal';

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

            // Update asterisks dynamically whenever mailbox changes
            setTimeout(updateRequiredAsterisks, 50);
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
    
    <script {!! \Helper::cspNonceAttr() !!}>
        function toggleReportType() {
            var isAnonim = document.querySelector('input[name="report_type"][value="anonim"]').checked;
            
            var cardTerbuka = document.getElementById('cardTerbuka');
            var cardAnonim = document.getElementById('cardAnonim');
            var inputName = document.getElementById('inputName');
            
            if (isAnonim) {
                cardTerbuka.classList.remove('active');
                cardAnonim.classList.add('active');
                
                document.getElementById('groupName').style.display = 'none';
                document.getElementById('groupEmail').style.display = 'none';
                
                inputName.removeAttribute('required');
                document.getElementById('inputEmail').removeAttribute('required');
                inputName.value = '';
                document.getElementById('inputEmail').value = '';
            } else {
                cardAnonim.classList.remove('active');
                cardTerbuka.classList.add('active');
                
                document.getElementById('groupName').style.display = 'block';
                document.getElementById('groupEmail').style.display = 'block';
                
                inputName.setAttribute('required', 'required');
                document.getElementById('inputEmail').setAttribute('required', 'required');
                
                @if(!empty($loggedName))
                    inputName.value = '{{ $loggedName }}';
                @endif
                @if(!empty($loggedEmail))
                    document.getElementById('inputEmail').value = '{{ $loggedEmail }}';
                @endif
            }
            
            if (typeof updateRequiredAsterisks === 'function') {
                updateRequiredAsterisks();
            }
        }
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            toggleReportType();
        });
    </script>
</body>

</html>
