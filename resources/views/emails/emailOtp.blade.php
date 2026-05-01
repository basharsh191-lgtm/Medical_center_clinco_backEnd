<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز التحقق - Clinco الطبي</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e8f4f8;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2c7da0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #1f5068;
            margin: 10px 0 0;
            font-size: 24px;
        }
        .logo {
            background-color: #c9e9f3;
            width: 80px;
            height:80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }
        .logo img {
            width: 50px;
            height: auto;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            text-align: center;
            background: #eef7fb;
            padding: 15px;
            border-radius: 12px;
            letter-spacing: 8px;
            color: #1f5068;
            margin: 20px 0;
            font-family: monospace;
            border: 1px solid #c9e9f3;
        }
        .message {
            color: #2d3e50;
            line-height: 1.6;
            text-align: center;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #7a8e9e;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0eef4;
        }
        .heart {
            color: #e74c3c;
        }
        .button {
            display: inline-block;
            background-color: #2c7da0;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            margin-top: 15px;
            font-size: 14px;
        }
        .expiry-text {
            margin-top: 30px;
            font-size: 13px;
            color: #a0aec0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="https://img.freepik.com/premium-vector/stethoscope-logo-template-medical-logo-inspiration-health-center-icon-design_48413-593.jpg?semt=ais_hybrid&w=740&q=80"
                    alt="Clinco Medical"
                    style="width: 45px; height: auto;">
            </div>
            <h1>🌿 رمز التحقق</h1>
            <p style="color: #5b8cac; margin-top: 5px;">مرحباً بك في المنصة الطبية المتكاملة</p>
        </div>

        @if($name)
            <p class="message">أهلاً بك <strong style="color: #2c7da0;">{{ $name }}</strong>،</p>
        @endif

        <p class="message">لديك خطوة واحدة لإتمام التسجيل في منصة <strong>Clinco</strong> الطبية.</p>
        <p class="message">استخدم الرمز التالي لتأكيد حسابك:</p>

        <div class="otp-code">
            {{ $otp }}
        </div>

        <p class="message">هذا الرمز يمكن استخدامه <strong>مرة واحدة فقط</strong> وينتهي صلاحيته خلال <strong>🔐10 دقائق  </strong>.</p>
        <div style="text-align: center; margin-top: 25px;">
            <span style="font-size: 13px; color: #5b8cac;">نسعى دائماً لرعاية صحتك</span>
        </div>

        <div class="footer">
             جميع الحقوق محفوظة لمنصة الطبية "Clinco"
             <br>
            &copy; {{ date('Y') }}
            <br>
        </div>
    </div>
</body>
</html>


{{-- <!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد الهوية | Clinco</title>
    <style>
        /* استيراد خط حديث */
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap');

        body {
            margin: 0; padding: 0;
            background-color: #f4f9fc;
            font-family: 'Tajawal', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .wrapper {
            width: 100%;
            padding: 40px 0;
            background-color: #f4f9fc;
        }

        .main-card {
            max-width: 500px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(44, 125, 160, 0.1);
            border: 1px solid #e1eef3;
        }

        .top-gradient {
            height: 8px;
            background: linear-gradient(90deg, #2c7da0, #8ecae6, #2c7da0);
        }

        .content {
            padding: 45px 35px;
            text-align: center;
        }

        .logo-bg {
            background: #f0f9ff;
            width: 85px;
            height: 85px;
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            transform: rotate(-5deg);
            box-shadow: 0 10px 20px rgba(44, 125, 160, 0.1);
            transition: 0.3s ease;
        }

        .logo-bg:hover {
            transform: rotate(0deg) scale(1.05);
        }

        h1 {
            color: #1a3a4a;
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 10px;
        }

        .subtitle {
            color: #7a9eb3;
            font-size: 15px;
            margin-bottom: 30px;
        }

        .welcome-box {
            background: linear-gradient(to left, #f8fdff, #ffffff);
            border-right: 5px solid #2c7da0;
            padding: 15px 20px;
            margin: 25px 0;
            text-align: right;
            border-radius: 8px;
            color: #3e5c6d;
            font-weight: 600;
        }

        /* تصميم منطقة الرمز OTP */
        .otp-container {
            margin: 40px 0;
            padding: 30px 20px;
            background: #fcfdfe;
            border-radius: 20px;
            border: 1px solid #edf4f8;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.02);
        }

        .otp-label {
            display: block;
            font-size: 13px;
            color: #94abb9;
            margin-bottom: 20px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .otp-wrapper {
            display: flex;
            justify-content: center;
            gap: 12px;
            direction: ltr; /* لترتيب المربعات من اليسار لليمين */
        }

        .digit-box {
            width: 55px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: 2px solid #e2edf3;
            border-radius: 15px;
            font-size: 32px;
            font-weight: 800;
            color: #2c7da0;
            box-shadow: 0 5px 15px rgba(44, 125, 160, 0.08);
            text-shadow: 1px 1px 0px white;
        }

        .timer-badge {
            margin-top: 25px;
            font-size: 12px;
            color: #e74c3c;
            background: #fff5f5;
            padding: 6px 15px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 700;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }

        .pulse-icon {
            animation: pulse 2s infinite;
        }

        .footer {
            padding: 30px;
            background: #fcfdfe;
            text-align: center;
            border-top: 1px solid #f1f6f9;
        }

        .help-link {
            color: #2c7da0;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            border-bottom: 1px dashed #2c7da0;
        }

        .copyright {
            margin-top: 20px;
            font-size: 11px;
            color: #b0c4d0;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <div class="top-gradient"></div>

            <div class="content">
                <div class="logo-bg">
                    <img src="https://img.freepik.com/premium-vector/stethoscope-logo-template-medical-logo-inspiration-health-center-icon-design_48413-593.jpg?w=120"
                         alt="Clinco" style="width: 50px; height: auto;">
                </div>

                <h1>تأكيد الهوية الرقمية</h1>
                <p class="subtitle">أمان بياناتك الطبية هي أولويتنا القصوى</p>

                @if($name)
                <div class="welcome-box">
                    مرحباً بك، د. {{ $name }} 👋
                </div>
                @endif

                <p style="color: #5b7a8a; font-size: 15px; line-height: 1.8;">
                    يرجى إدخال الرمز السري التالي لإتمام عملية تسجيل الدخول إلى منصة <strong>Clinco</strong> الطبية:
                </p>

                <div class="otp-container">
                    <span class="otp-label">رمز التحقق المؤقت (OTP)</span>
                    <div class="otp-wrapper">
                        @foreach(str_split((string)$otp) as $digit)
                            <div class="digit-box">{{ $digit }}</div>
                        @endforeach
                    </div>

                    <div class="timer-badge">
                        <span class="pulse-icon">⏳</span>
                        صالح لمدة 10 دقائق فقط
                    </div>
                </div>

                <p style="font-size: 12px; color: #a0b1bb;">
                    إذا لم تطلب هذا الرمز، يمكنك تجاهل هذا البريد بأمان.
                </p>
            </div>

            <div class="footer">
                <a href="#" class="help-link">مركز الدعم والمساعدة</a>
                <div class="copyright">
                    جميع الحقوق محفوظة لـ Clinco Medical Solutions &copy; {{ date('Y') }}
                    <br>
                    نعمل من أجل رعاية صحية أسرع وأكثر أماناً 🛡️
                </div>
            </div>
        </div>
    </div>
</body>
</html> --}}
