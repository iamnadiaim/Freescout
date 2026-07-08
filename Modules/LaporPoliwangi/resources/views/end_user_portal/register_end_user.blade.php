<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun Pelapor - Lapor Poliwangi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-box {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.12);
        }

        .register-title {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            text-align: center;
        }

        .register-subtitle {
            margin: 0 0 24px;
            font-size: 14px;
            color: #64748b;
            text-align: center;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-control {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d6dee8;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 14px;
            outline: none;
        }

        .form-control:focus {
            border-color: #0f7bdc;
            box-shadow: 0 0 0 3px rgba(15, 123, 220, 0.12);
        }

        .btn-register {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 12px 16px;
            background: #0f7bdc;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-register:hover {
            background: #0b66b7;
        }

        .alert-error {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #fee2e2;
            color: #991b1b;
            font-size: 14px;
        }

        .alert-error ul {
            margin: 0;
            padding-left: 18px;
        }

        .login-link {
            margin-top: 18px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }

        .login-link a {
            color: #0f7bdc;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .back-link {
            margin-top: 12px;
            text-align: center;
            font-size: 13px;
        }

        .back-link a {
            color: #64748b;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

@php
    $redirectUrl = old('redirect', isset($redirect) ? $redirect : url('/help'));
@endphp

<div class="register-box">
    <h1 class="register-title">Daftar Akun Pelapor</h1>
    <p class="register-subtitle">
        Buat akun untuk mengirim laporan dan melihat status tiket.
    </p>

    @if ($errors->any())
        <div class="alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('laporpoliwangi.end_user_portal.register.submit') }}">
        {{ csrf_field() }}

        <input type="hidden" name="redirect" value="{{ $redirectUrl }}">

        <div class="form-group">
            <input type="text"
                   name="name"
                   class="form-control"
                   value="{{ old('name') ?? '' }}"
                   placeholder="Nama lengkap"
                   required
                   autofocus>
        </div>

        <div class="form-group">
            <input type="email"
                   name="email"
                   class="form-control"
                   value="{{ old('email') ?? '' }}"
                   placeholder="Email"
                   required>
        </div>

        <div class="form-group">
            <input type="password"
                   name="password"
                   class="form-control"
                   placeholder="Password"
                   required>
        </div>

        <div class="form-group">
            <input type="password"
                   name="password_confirmation"
                   class="form-control"
                   placeholder="Konfirmasi password"
                   required>
        </div>

        <button type="submit" class="btn-register">
            Daftar
        </button>
    </form>

    <div class="login-link">
        Sudah punya akun?
        <a href="{{ route('laporpoliwangi.end_user_portal.login_end_user', ['redirect' => $redirectUrl]) }}">
            Login di sini
        </a>
    </div>

    <div class="back-link">
        <a href="{{ $redirectUrl }}">
            Kembali ke halaman laporan
        </a>
    </div>
</div>

</body>
</html>
