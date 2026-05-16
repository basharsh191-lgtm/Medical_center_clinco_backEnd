<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>رمز التحقق - Clinco الطبي</title>
    <style type="text/css">
        /* لضمان ثبات الخطوط في الصناديق التي لا تدعم خط كابرو الخارجي */
        body, table, td, p, div, h1 { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        @media only screen and (max-width: 500px) {
            .otp-text { font-size: 36px !important; letter-spacing: 6px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #e0f2f7; direction: rtl;" bgcolor="#e0f2f7">

    <!-- الجدول الرئيسي المحيط بالإيميل كبديل للـ Container -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #e0f2f7;" bgcolor="#e0f2f7">
        <tr>
            <td align="center" style="padding: 30px 15px;">

                <!-- الكرت الأبيض الأساسي للإيميل -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 520px; background-color: #ffffff; border-radius: 40px; overflow: hidden; border: 1px solid rgba(90, 170, 200, 0.2); box-shadow: 0 25px 45px rgba(0, 30, 40, 0.15);" bgcolor="#ffffff">

                    <!-- الهيدر والشعار -->
                    <tr>
                        <td align="center" style="padding: 40px 32px 15px 32px;">
                            <div style="margin-bottom: 16px;">
                                <img src="{{ $message->embed(public_path('storage/logo/logo.jpg')) }}" alt="Clinco" width="80" style="display: block; border-radius: 20px; outline: none; text-decoration: none;" />
                            </div>
                            <h1 style="color: #146b8a; font-size: 26px; font-weight: 800; margin: 8px 0 6px 0;">🌿 Clinco رمز التحقق</h1>
                            <p style="color: #2f86a6; font-size: 14px; font-weight: 600; margin: 0; background-color: #e6f4f9; display: inline-block; padding: 5px 18px; border-radius: 40px;">منظومة رعاية صحية ذكية</p>
                        </td>
                    </tr>

                    <!-- رسالة الترحيب التفاعلية -->
                    <tr>
                        <td align="center" style="padding: 10px 32px; color: #1e4a5f; font-size: 16px; line-height: 1.7; text-align: center;">
                            @if($name)
                                <p style="margin: 0 0 10px 0;">✨ مرحباً بك <strong style="background-color: #e1f0f5; padding: 4px 14px; border-radius: 50px; color: #0f5e7c; font-weight: 800;">{{ $name }}</strong></p>
                            @else
                                <p style="margin: 0 0 10px 0;">أهلاً بك في رحلة العناية بصحتك ✨</p>
                            @endif

                            <p style="margin: 0;">
                                <strong>خطوة واحدة تفصل بينك وبين خدمات Clinco المتكاملة</strong><br />
                                استخدم الرمز السري أدناه لإتمام التسجيل:
                            </p>
                        </td>
                    </tr>

                    <!-- منطقة كرت الـ OTP الجداول البديلة -->
                    <tr>
                        <td style="padding: 20px 32px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fafefe; border-radius: 32px; padding: 25px 15px; border: 1px solid #d4edf5;" bgcolor="#fafefe">
                                <tr>
                                    <td align="center">
                                        <!-- صندوق الرمز العريض والمستقر بدون فليكس بوكس -->
                                        <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                            <tr>
                                                <td class="otp-text" align="center" style="background-color: #ffffff; border: 1px solid #e2f0f5; border-radius: 28px; color: #0b5e7e; font-size: 44px; font-weight: 800; padding: 12px 35px; letter-spacing: 10px; direction: ltr; font-family: 'Courier New', Courier, monospace; box-shadow: 0 2px 6px rgba(0,0,0,0.02);" bgcolor="#ffffff">
                                                    {{ $otp }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- شريط المعلومات الأمني -->
                    <tr>
                        <td style="padding: 0 32px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #eef6fa; border-radius: 60px;">
                                <tr>
                                    <td align="center" style="padding: 10px 18px; color: #146b8a; font-size: 13px; font-weight: 600;">
                                        🔐 صالح لمرة واحدة فقط ⏱️ ينتهي خلال 10 دقائق
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- عبارة الفريق الترويجية -->
                    <tr>
                        <td align="center" style="padding: 20px 32px 10px 32px;">
                            <p style="margin: 0; background-color: #eef3f7; border-radius: 60px; padding: 8px 22px; display: inline-block; font-size: 13px; font-weight: 700; color: #1f7a9c;">
                                ⚕️ فريق Clinco يضع صحتك أولاً
                            </p>
                        </td>
                    </tr>

                    <!-- الفوتر وحقوق الحفظ -->
                    <tr>
                        <td align="center" style="padding: 30px 32px 40px 32px; border-top: 1px solid #dcebf2; color: #7aa9bf; font-size: 12px; line-height: 1.6; text-align: center;">
                            <p style="margin: 0 0 8px 0;">
                                📧 إذا لم تطلب هذا الرمز، يمكنك تجاهل البريد بأمان.<br />
                                جميع الحقوق محفوظة لمنصة Clinco الطبية © {{ date('Y') }}
                            </p>
                            <div style="color: #88b8ce; font-size: 12px; margin-top: 8px;">
                                برعاية <span style="color: #f28b82;">❤️</span> العناية المتكاملة
                            </div>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
