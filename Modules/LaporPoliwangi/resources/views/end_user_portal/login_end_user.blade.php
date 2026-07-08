<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Log In - Lapor Poliwangi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #ffffff;
            color: #2f3d4a;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .login-page {
            min-height: calc(100vh - 110px);
            padding: 52px 20px 70px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .login-alert {
            width: 610px;
            max-width: 100%;
            margin-bottom: 15px;
            padding: 11px 13px;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.4;
        }

        .alert-danger {
            background: #fdecec;
            border: 1px solid #f5bcbc;
            color: #9b1c1c;
        }

        .alert-success {
            background: #e7f8ee;
            border: 1px solid #b8eacb;
            color: #226c3b;
        }

        .login-card {
            width: 610px;
            max-width: 100%;
            background: #ffffff;
            border: 1px solid #cfd8df;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .12);
            padding: 0 20px 38px;
        }

        .login-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #25384b;
            margin: 0;
            padding: 28px 0 8px;
        }

        .login-subtitle {
            margin: 0 0 24px;
            text-align: center;
            font-size: 14px;
            color: #6b7c8f;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-control {
            width: 100%;
            height: 46px;
            border: 1px solid #79bdf3;
            border-radius: 3px;
            padding: 10px 12px;
            font-size: 16px;
            color: #2f3d4a;
            outline: none;
            background: #ffffff;
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
        }

        .form-control:focus {
            border-color: #4aa3e8;
            box-shadow: 0 0 5px rgba(0, 120, 212, .35);
        }

        .form-control::placeholder {
            color: #a8b4c0;
        }

        .btn-login {
            width: 100%;
            height: 56px;
            border: none;
            border-radius: 5px;
            background: #0875cf;
            color: #ffffff;
            font-size: 20px;
            font-weight: normal;
            cursor: pointer;
            transition: background .15s ease-in-out;
        }

        .btn-login:hover {
            background: #006bbd;
        }

        .btn-login:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(8, 117, 207, .25);
        }

        .login-link {
            text-align: center;
            margin-top: 16px;
            font-size: 14px;
            color: #5d6f82;
        }

        .login-link a {
            color: #0875cf;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .back-link {
            text-align: center;
            margin-top: 12px;
            font-size: 13px;
        }

        .back-link a {
            color: #6b7c8f;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .login-page {
                min-height: calc(100vh - 130px);
                padding: 35px 14px 55px;
            }

            .login-card {
                padding: 0 16px 30px;
            }

            .login-title {
                font-size: 22px;
                padding: 25px 0 8px;
            }

            .form-control {
                height: 44px;
                font-size: 15px;
            }

            .btn-login {
                height: 52px;
                font-size: 18px;
            }
        }
    </style>
</head>

<body>

    @php
        $redirectUrl = old('redirect', isset($redirect) ? $redirect : url('/help'));
        $loggedEmail = session('end_user_portal_email');
    @endphp

    {{-- NAVBAR --}}
    @include('laporpoliwangi::end_user_portal.partials.navbar', [
        'mailbox' => null,
        'email' => $loggedEmail,
    ])

    <div class="login-page">

        @if (session('success'))
            <div class="login-alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="login-alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="login-card">
            <h1 class="login-title">Log In</h1>
            <p class="login-subtitle">
                Masuk ke akun pelapor Lapor Poliwangi.
            </p>

            <form method="POST" action="{{ route('laporpoliwangi.end_user_portal.login.submit') }}">
                {{ csrf_field() }}

                <input type="hidden" name="redirect" value="{{ $redirectUrl }}">

                <div class="form-group">
                    <input type="email"
                           name="email"
                           class="form-control"
                           value="{{ old('email') ?? '' }}"
                           placeholder="Email Address"
                           required
                           autofocus>
                </div>

                <div class="form-group">
                    <input type="password"
                           name="password"
                           class="form-control"
                           placeholder="Password"
                           required>
                </div>

                <button type="submit" class="btn-login">
                    Login
                </button>

                <div class="login-link">
                    Belum punya akun?
                    <a href="{{ route('laporpoliwangi.end_user_portal.register', ['redirect' => $redirectUrl]) }}">
                        Daftar di sini
                    </a>
                </div>

                <div class="back-link">
                    <a href="{{ $redirectUrl }}">
                        Kembali ke halaman laporan
                    </a>
                </div>
            </form>
        </div>
    </div>

    @include('laporpoliwangi::end_user_portal.partials.footer', [
        'mailbox' => null,
        'setting' => null,
    ])

</body>

</html>
