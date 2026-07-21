<!DOCTYPE html>
<html>
<head>
    <title>Verifikasi Akun Portal Poliwangi Portal</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
        <h2 style="color: #0056b3; margin-top: 0;">Verifikasi Akun Anda</h2>
        
        <p>Halo,</p>
        
        <p>Terima kasih telah mendaftar di <strong>Portal Poliwangi Portal</strong>. Untuk mengaktifkan akun portal Anda dan memastikan keamanan email Anda, silakan klik tombol verifikasi di bawah ini:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background-color: #0056b3; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">
                Verifikasi Email Saya
            </a>
        </div>
        
        <p>Atau Anda juga bisa menyalin dan menempelkan link berikut di browser Anda:</p>
        <p style="word-break: break-all; color: #555;">
            <a href="{{ $url }}" style="color: #0056b3;">{{ $url }}</a>
        </p>
        
        <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
        
        <p style="font-size: 12px; color: #777; margin-bottom: 0;">
            Jika Anda tidak merasa mendaftar di Portal Poliwangi Portal, silakan abaikan email ini.<br>
            Link ini dibuat secara otomatis, mohon tidak membalas email ini.
        </p>
    </div>
</body>
</html>
