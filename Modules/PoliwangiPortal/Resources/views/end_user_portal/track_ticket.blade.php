<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cek Status Tiket - Poliwangi Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f7f9fc;
            color: #1f2f3d;
        }

        .portal-home {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(10, 132, 223, .10), transparent 34%),
                linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);
        }

        .track-container {
            max-width: 500px;
            margin: 80px auto;
            padding: 32px;
            background: #ffffff;
            border: 1px solid #e6edf5;
            border-radius: 24px;
            box-shadow: 0 16px 42px rgba(15, 23, 42, .07);
        }

        .track-title {
            margin: 0 0 16px;
            color: #102a43;
            font-size: 24px;
            font-weight: 800;
            text-align: center;
        }

        .track-desc {
            text-align: center;
            color: #52616f;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 800;
            color: #25384b;
        }

        .form-control {
            width: 100%;
            height: 48px;
            border: 1px solid #cfd8e3;
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 16px;
            color: #25384b;
            outline: none;
            background: #ffffff;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #0a84df;
            box-shadow: 0 0 0 3px rgba(10, 132, 223, .12);
        }

        .btn-track {
            width: 100%;
            border: none;
            background: #0a84df;
            color: #ffffff;
            font-size: 16px;
            font-weight: 800;
            padding: 14px;
            border-radius: 12px;
            cursor: pointer;
            transition: background .15s ease-in-out;
            box-shadow: 0 12px 24px rgba(10, 132, 223, .20);
        }

        .btn-track:hover {
            background: #0877c8;
        }

        .alert-danger {
            background: #fdecec;
            border: 1px solid #f5bcbc;
            color: #9b1c1c;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .login-prompt {
            margin-top: 24px;
            text-align: center;
            font-size: 14px;
            color: #52616f;
            padding-top: 20px;
            border-top: 1px solid #e8edf3;
        }

        .login-prompt a {
            color: #0a84df;
            font-weight: 800;
            text-decoration: none;
        }

        .login-prompt a {
            color: #0a84df;
            font-weight: 800;
            text-decoration: none;
        }

        .login-prompt a:hover {
            text-decoration: underline;
        }

        /* Tabs CSS */
        .tabs {
            display: flex;
            margin-bottom: 24px;
            border-bottom: 2px solid #e6edf5;
        }
        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 12px 16px;
            font-size: 15px;
            font-weight: 800;
            color: #7b8794;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s ease;
        }
        .tab-btn:hover {
            color: #102a43;
        }
        .tab-btn.active {
            color: #0a84df;
            border-bottom-color: #0a84df;
        }
        .tab-pane {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }
        .tab-pane.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <div class="portal-home">
        @include('poliwangiportal::end_user_portal.partials.navbar')

        <div class="track-container">
            <h2 class="track-title">Cek Status Laporan</h2>
            <p class="track-desc">Pilih metode pelacakan sesuai dengan cara Anda mengirim laporan sebelumnya.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin-bottom: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div style="background: #e8f7ee; border: 1px solid #10b981; color: #047857; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; text-align: center;">
                    {{ session('success') }}
                </div>
            @endif

            <div class="tabs">
                <button type="button" class="tab-btn active" onclick="switchTab('email')">Pelaporan Terbuka</button>
                <button type="button" class="tab-btn" onclick="switchTab('anonim')">Pelaporan Anonim</button>
            </div>

            <!-- Tab Email -->
            <div id="tab-email" class="tab-pane active">
                <form action="{{ route('PoliwangiPortal.end_user_portal.track.submit') }}" method="POST">
                    {{ csrf_field() }}
                    
                    <div class="form-group">
                        <label class="form-label">Nomor Tiket</label>
                        <input type="text" name="ticket_number" class="form-control" placeholder="Masukkan Nomor Tiket" value="{{ old('ticket_number') ?? '' }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Anda</label>
                        <input type="email" name="email" class="form-control" placeholder="Masukkan Email" value="{{ old('email') ?? '' }}" required>
                    </div>

                    <button type="submit" class="btn-track">Kirim Tautan Akses ke Email</button>
                </form>
            </div>

            <!-- Tab Anonim -->
            <div id="tab-anonim" class="tab-pane">
                <form action="{{ route('PoliwangiPortal.end_user_portal.track.submit') }}" method="POST">
                    {{ csrf_field() }}
                    
                    <div class="form-group">
                        <label class="form-label">Kode Akses Pelacak</label>
                        <input type="text" name="tracking_code" class="form-control" placeholder="Masukkan Kode Akses Pelacak" value="{{ old('tracking_code') ?? '' }}" required>
                    </div>

                    <button type="submit" class="btn-track">Cek Status dengan Kode</button>
                </form>
            </div>

            <div id="anonymousHistorySection" style="display: none; margin-top: 32px; border-top: 1px solid #e8edf3; padding-top: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                    <h3 style="font-size: 16px; color: #102a43; margin: 0; font-weight: 800;">Riwayat di Perangkat Ini</h3>
                    <button type="button" onclick="clearAnonymousHistory()" style="background: none; border: none; color: #e11d48; font-size: 13px; font-weight: 700; cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: background 0.2s;">
                        Hapus Riwayat
                    </button>
                </div>
                <div id="anonymousHistoryList" style="display: flex; flex-direction: column; gap: 12px;">
                    <!-- Diisi lewat JS -->
                </div>
                <p style="font-size: 12px; color: #64748b; margin-top: 12px; line-height: 1.4;">
                    <strong>Info Keamanan:</strong> Riwayat ini hanya tersimpan di peramban (browser) Anda. Pastikan untuk menghapus riwayat jika Anda menggunakan komputer publik.
                </p>
            </div>

            <div class="login-prompt">
                Punya akun? <a href="{{ route('PoliwangiPortal.end_user_portal.login_end_user', ['redirect' => route('PoliwangiPortal.end_user_portal.my_ticket')]) }}">Login di sini</a> untuk melihat semua tiket Anda.
            </div>
        </div>
    </div>

    <script {!! \Helper::cspNonceAttr() !!}>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                const historyStr = localStorage.getItem('anonymous_tickets_history');
                if (historyStr) {
                    const history = JSON.parse(historyStr);
                    if (history && history.length > 0) {
                        const historySection = document.getElementById('anonymousHistorySection');
                        const historyList = document.getElementById('anonymousHistoryList');
                        
                        let html = '';
                        history.forEach(ticket => {
                            html += `
                                <a href="javascript:void(0)" onclick="fillAndSubmit('${ticket.number}')" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: inherit; transition: all 0.2s;">
                                    <div style="flex: 1; min-width: 0; padding-right: 12px;">
                                        <div style="font-size: 14px; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px;">
                                            ${ticket.subject || 'Laporan'}
                                        </div>
                                        <div style="font-size: 12px; color: #64748b;">
                                            ${ticket.date || '-'}
                                        </div>
                                    </div>
                                    <div style="font-weight: 800; color: #0a84df; font-size: 14px;">
                                        #${ticket.number}
                                    </div>
                                </a>
                            `;
                        });
                        
                        historyList.innerHTML = html;
                        historySection.style.display = 'block';
                    }
                }
            } catch (e) {
                console.error('Gagal meload riwayat tiket', e);
            }
        });

        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            
            if (tab === 'email') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('tab-email').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('tab-anonim').classList.add('active');
            }
        }

        function fillAndSubmit(number) {
            if (number.startsWith('WB-')) {
                switchTab('anonim');
                document.querySelector('input[name="tracking_code"]').value = number;
                document.querySelector('#tab-anonim form').submit();
            } else {
                switchTab('email');
                document.querySelector('input[name="ticket_number"]').value = number;
                document.querySelector('input[name="email"]').focus();
            }
        }

        function clearAnonymousHistory() {
            if (confirm('Apakah Anda yakin ingin menghapus riwayat laporan di perangkat ini?')) {
                localStorage.removeItem('anonymous_tickets_history');
                document.getElementById('anonymousHistorySection').style.display = 'none';
                document.getElementById('anonymousHistoryList').innerHTML = '';
            }
        }
    </script>
</body>
</html>
