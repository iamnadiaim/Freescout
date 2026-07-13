<!DOCTYPE html>
<html>
<head>
    <title>Tautan Akses Pelacakan Laporan</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
        <h2 style="color: #0056b3; margin-top: 0;">Pelacakan Laporan #{{ $conversation->number }}</h2>
        
        <p>Halo,</p>
        
        <p>Seseorang (kemungkinan Anda) telah meminta tautan akses aman untuk melacak laporan dengan Nomor Tiket <strong>#{{ $conversation->number }}</strong> (Subjek: <em>{{ $conversation->subject }}</em>).</p>
        
        <p>Untuk melihat detail status laporan dan balasan dari admin, silakan klik tombol di bawah ini. <strong>Tautan ini hanya berlaku selama 24 jam.</strong></p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background-color: #0056b3; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">
                Cek Status Laporan
            </a>
        </div>
        
        <p>Atau Anda juga bisa menyalin dan menempelkan link berikut di browser Anda:</p>
        <p style="word-break: break-all; color: #555;">
            <a href="{{ $url }}" style="color: #0056b3;">{{ $url }}</a>
        </p>
        
        <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
        
        <p style="font-size: 12px; color: #777; margin-bottom: 0;">
            Jika Anda tidak meminta tautan pelacakan ini, silakan abaikan email ini. Tidak ada orang yang dapat melihat tiket Anda tanpa tautan ini.<br>
            Link ini dibuat secara otomatis, mohon tidak membalas email ini.
        </p>
    </div>
</body>
</html>
