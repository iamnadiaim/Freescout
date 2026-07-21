<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rating Submitted</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            background-color: #f5f8fa;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .thank-you-box {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .emoji {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            margin: 0 0 10px 0;
            color: #1f2d3d;
        }
        p {
            font-size: 16px;
            color: #555;
            margin: 0;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <div class="thank-you-box">
        @php
            $emoji = '✅';
            $label = 'Terima Kasih';
            
            if ($rating == 1 || $rating === 'great') {
                $emoji = $setting->rating_1_emoji ?? '😍';
                $label = $setting->rating_1_label ?? 'Bagus Sekali';
            } elseif ($rating == 2 || $rating === 'okay') {
                $emoji = $setting->rating_2_emoji ?? '😐';
                $label = $setting->rating_2_label ?? 'Biasa Saja';
            } elseif ($rating == 3 || $rating === 'not_good') {
                $emoji = $setting->rating_3_emoji ?? '😡';
                $label = $setting->rating_3_label ?? 'Buruk';
            }
        @endphp

        <div class="emoji">{{ $emoji }}</div>
        <h1>{{ $label }}</h1>
        <p>Terima kasih atas masukan dan penilaian Anda!</p>
    </div>

</body>
</html>
