r<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>مرحباً بك في منصة Clinco الطبية</title>
    <style type="text/css">
        body, table, td, a { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        a { text-decoration: none; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #e0f2f7; direction: rtl;" bgcolor="#e0f2f7">

    <!-- الجدول الرئيسي المحيط بالإيميل بالكامل -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #e0f2f7;" bgcolor="#e0f2f7">
        <tr>
            <td align="center" style="padding: 40px 10px;">

                <!-- كرت الإيميل الرئيسي الأبيض -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px; background-color: #ffffff; border-radius: 30px; overflow: hidden; border: 1px solid #c9e3ef; box-shadow: 0 15px 30px rgba(0,0,0,0.05);" bgcolor="#ffffff">

                    <!-- الهيدر والشعار -->
                    <tr>
                        <td align="center" style="padding: 35px 30px 10px 30px;">
                            @php($logoPath = public_path('storage/logo/logo.jpg'))
                            @if(file_exists($logoPath))
                            <img src="{{ $message->embed($logoPath) }}" alt="Clinco" width="80" style="display: block; border-radius: 20px; outline: none; text-decoration: none;" />
                            @endif
                            <h1 style="color: #146b8a; font-size: 22px; font-weight: 800; margin: 15px 0 5px 0; font-family: 'Segoe UI', Tahoma, sans-serif;">أهلاً بك في Clinco 🌿</h1>
                            <p style="color: #2f86a6; font-size: 13px; font-weight: 600; margin: 0; background-color: #e6f4f9; display: inline-block; padding: 5px 15px; border-radius: 20px;">حسابك الطبي الشخصي جاهز الآن</p>
                        </td>
                    </tr>

                    <!-- الرسالة الترحيبية -->
                    <tr>
                        <td align="center" style="padding: 10px 30px 20px 30px; color: #1e4a5f; font-size: 15px; line-height: 1.6; text-align: center;">
                            عزيزنا المريض، تم إنشاء ملفك الطبي وحسابك الإلكتروني على <strong>منصة Clinco الطبية</strong> بنجاح.<br />
                            يمكنك الآن استخدام البيانات أدناه لتسجيل الدخول، متابعة مواعيدك، واستعراض سجلاتك الطبية بكل سهولة:
                        </td>
                    </tr>

                    <!-- منطقة البيانات (البريد والباسورد) -->
                    <tr>
                        <td style="padding: 0 30px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fafefe; border-radius: 20px; padding: 20px; border: 1px solid #d4edf5;" bgcolor="#fafefe">

                                <!-- حقل البريد -->
                                <tr>
                                    <td align="right" style="padding-bottom: 5px; color: #7a9eb3; font-size: 12px; font-weight: 700;">📱 البريد الإلكتروني المسجل:</td>
                                </tr>
                                <tr>
                                    <td align="left" style="padding: 10px 14px; background-color: #ffffff; border: 1px solid #e2f0f5; border-radius: 10px; color: #0b5e7e; font-weight: 600; font-size: 14px; direction: ltr; font-family: Arial, sans-serif;" bgcolor="#ffffff">
                                        {{ $email }}
                                    </td>
                                </tr>

                                <!-- مسافة فاصلة -->
                                <tr><td height="15"></td></tr>

                                <!-- حقل كلمة المرور -->
                                <tr>
                                    <td align="right" style="padding-bottom: 5px; color: #7a9eb3; font-size: 12px; font-weight: 700;">🔑 كلمة المرور المؤقتة:</td>
                                </tr>
                                <tr>
                                    <td align="left" style="padding: 10px 14px; background-color: #ffffff; border: 1px solid #e2f0f5; border-radius: 10px; color: #0b5e7e; font-weight: 600; font-size: 14px; direction: ltr; font-family: Arial, sans-serif;" bgcolor="#ffffff">
                                        {{ $password }}
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- زر الدخول المباشر -->
                    <tr>
                        <td align="center" style="padding: 30px 30px 15px 30px;">
                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate;">
                                <tr>
                                    <td align="center" style="border-radius: 25px; background-color: #146b8a;" bgcolor="#146b8a">
                                        <a href="{{ url('/login') }}" target="_blank" style="font-size: 14px; font-weight: bold; color: #ffffff; padding: 12px 35px; display: inline-block; background-color: #146b8a; border-radius: 25px; border: 1px solid #146b8a; font-family: 'Segoe UI', Tahoma, sans-serif;">📲 تسجيل الدخول إلى حسابك</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- التنبيه الأمني -->
                    <tr>
                        <td style="padding: 10px 30px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fff5f5; border-radius: 30px; border: 1px solid #ffe3e3;" bgcolor="#fff5f5">
                                <tr>
                                    <td align="center" style="padding: 10px 15px; color: #e74c3c; font-size: 12px; font-weight: 700; line-height: 1.4;">
                                        🔒 للحفاظ على خصوصية ملفك الطبي، نوصي بتغيير كلمة المرور المؤقتة بعد تسجيل الدخول الأول مباشرة.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- الفوتر -->
                    <tr>
                        <td align="center" style="padding: 30px 30px; border-top: 1px solid #dcebf2; color: #7aa9bf; font-size: 11px; line-height: 1.5; text-align: center;">
                            تم إرسال هذا البريد آلياً من نظام Clinco لإدارة الخدمات الطبية.<br />
                            نتمنى لك دوام الصحة والعافية © {{ date('Y') }}
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>