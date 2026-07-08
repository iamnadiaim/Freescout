<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Metode Pelaporan</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background:
                linear-gradient(rgba(255, 255, 255, 0.78), rgba(255, 255, 255, 0.78)),
                url("{{ asset('img/bg-login.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 920px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 20px 55px rgba(15, 23, 42, 0.16);
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
        }

        .left-panel {
            min-height: 360px;
            padding: 42px 38px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .logo {
            width: 165px;
            height: auto;
            margin-bottom: 22px;
        }

        .brand-title {
            margin: 0;
            color: #1f2937;
            font-size: 22px;
            font-weight: 800;
            text-align: center;
        }

        .brand-subtitle {
            margin: 8px 0 0;
            color: #6b7280;
            font-size: 14px;
            text-align: center;
            line-height: 1.5;
        }

        .right-panel {
            min-height: 360px;
            padding: 42px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #fbfdff;
        }

        .login-title {
            margin: 0;
            color: #111827;
            font-size: 20px;
            font-weight: 800;
            text-align: center;
        }

        .login-desc {
            margin: 8px 0 24px;
            color: #6b7280;
            font-size: 13px;
            text-align: center;
            line-height: 1.5;
        }

        .btn {
            width: 100%;
            min-height: 48px;
            margin-bottom: 12px;
            padding: 13px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.22s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
        }

        .btn-sso {
            background: #1f3b57;
            color: #ffffff;
        }

        .btn-email {
            background: #0a84df;
            color: #ffffff;
        }

        .btn-anon {
            background: #ffffff;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-anon:hover {
            background: #dc2626;
            color: #ffffff;
        }

        form {
            margin: 0;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 12px 0 18px;
            color: #9ca3af;
            font-size: 12px;
            font-weight: 700;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .footer-links {
            margin-top: 18px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }

        .footer-links a {
            color: #0a84df;
            text-decoration: none;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .login-card {
                max-width: 420px;
                grid-template-columns: 1fr;
            }

            .left-panel {
                min-height: auto;
                padding: 32px 24px 24px;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
            }

            .right-panel {
                min-height: auto;
                padding: 30px 24px 34px;
            }

            .logo {
                width: 130px;
            }

            .brand-title {
                font-size: 19px;
            }
        }
    </style>
</head>

<body>

    <div class="login-card">

        <div class="left-panel">
            <img class="logo" src="{{ asset('img/logo_poliwangi.png') }}" alt="Logo Poliwangi">

            <h1 class="brand-title">Lapor Poliwangi</h1>

            <p class="brand-subtitle">
                Portal layanan pelaporan dan bantuan<br>
                Politeknik Negeri Banyuwangi
            </p>
        </div>

        <div class="right-panel">

            <h2 class="login-title">Pilih Metode Laporan</h2>

            <p class="login-desc">
                Silakan pilih metode masuk untuk membuat laporan.
            </p>

            {{-- SSO --}}
            <a class="btn btn-sso"
                href="{{ route('laporpoliwangi.end_user_portal.sso.poliwangi', ['redirect' => url('/help')]) }}">
                Masuk dengan SSO Poliwangi
            </a>

            {{-- LOGIN EMAIL --}}
            <a class="btn btn-email"
                href="{{ route('laporpoliwangi.end_user_portal.login_end_user') }}">
                Masuk dengan Email + Password
            </a>

        </div>

    </div>

</body>

</html>
