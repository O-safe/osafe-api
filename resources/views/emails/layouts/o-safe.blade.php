<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <title>{{ $title ?? 'O SAFE Security Notification' }}</title>
    <style type="text/css">
        /* Client-specific Resets */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0f172a; }

        /* Mobile Responsive Styles */
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .content-padding { padding: 25px 18px !important; }
            .header-padding { padding: 25px 20px !important; }
            .header-title { font-size: 20px !important; }
            .mobile-stack { display: block !important; width: 100% !important; }
            .mobile-center { text-align: center !important; }
            .otp-code { font-size: 28px !important; letter-spacing: 5px !important; }
            .otp-box { min-width: 80% !important; padding: 18px 12px !important; }
            .button-cta { width: 100% !important; display: block !important; text-align: center !important; box-sizing: border-box !important; }
        }

        /* Dark mode meta & rules */
        @media (prefers-color-scheme: dark) {
            body, .email-wrapper { background-color: #0f172a !important; }
            .email-container { background-color: #ffffff !important; color: #0f172a !important; }
            .footer-text { color: #94a3b8 !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; -webkit-font-smoothing: antialiased;">
    <!-- Outer Wrapper -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-wrapper" style="background-color: #f4f6f9; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 30px 10px 40px 10px;">
                <!-- Main Container -->
                <!--[if (gte mso 9)|(IE)]>
                <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                <tr>
                <td align="center" valign="top" width="600">
                <![endif]-->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; margin: 0 auto;">

                    <!-- Brand Header -->
                    <tr>
                        <td align="left" class="header-padding" style="background: linear-gradient(135deg, #00352F 0%, #004D40 100%); padding: 32px 35px; border-bottom: 3px solid #00D639;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="left" valign="middle">
                                        <a href="{{ config('app.frontend_url', config('app.url')) }}" target="_blank" style="text-decoration: none;">
                                            <img src="{{ asset('images/branding/osafe-logo-horizontal.png') }}" alt="O SAFE Security" width="170" style="width: 170px; max-width: 170px; height: auto; display: block; border: 0;" />
                                        </a>
                                    </td>
                                    @if(isset($categorySubtitle))
                                    <td align="right" valign="middle" style="text-align: right;">
                                        <span style="display: inline-block; background-color: rgba(0, 214, 57, 0.15); color: #00D639; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; padding: 6px 14px; border-radius: 20px; border: 1px solid rgba(0, 214, 57, 0.3);">
                                            {{ $categorySubtitle }}
                                        </span>
                                    </td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if(isset($securityBanner))
                    <!-- Optional Security Alert Banner -->
                    <tr>
                        <td align="center" style="background-color: #fef2f2; border-bottom: 1px solid #fca5a5; padding: 12px 20px; color: #991b1b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px;">
                            🛡️ {{ $securityBanner }}
                        </td>
                    </tr>
                    @endif

                    <!-- Content Body -->
                    <tr>
                        <td align="left" class="content-padding" style="padding: 40px 35px; background-color: #ffffff; color: #0f172a; font-size: 15px; line-height: 1.6;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 30px 25px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 12px; line-height: 1.6; text-align: center;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-bottom: 12px;">
                                        <img src="{{ asset('images/branding/osafe-logo-shield.png') }}" alt="O SAFE Shield" width="28" style="width: 28px; height: auto; opacity: 0.8; display: block;" />
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="color: #0f172a; font-weight: 700; font-size: 13px; letter-spacing: 0.5px;">
                                        O SAFE Security Layer
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="color: #64748b; font-size: 12px; padding-top: 4px;">
                                        Next-Gen Physical Security, Realtime Telemetry & Family Safety
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top: 16px; color: #94a3b8; font-size: 11px;">
                                        &copy; {{ date('Y') }} O SAFE Security. All rights reserved.<br />
                                        This is an automated security transmission. Please do not reply directly to this message.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
                <!--[if (gte mso 9)|(IE)]>
                </td>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
