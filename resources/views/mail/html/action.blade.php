<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>{{ config('app.name') }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        body { width: 100% !important; margin: 0 !important; padding: 0 !important; background-color: #f5f5f7; }

        @media only screen and (max-width: 620px) {
            .card { border-radius: 0 !important; }
            .outer { padding: 0 !important; }
            .cell-header, .cell-body, .cell-footer { padding-left: 24px !important; padding-right: 24px !important; }
            .greeting { font-size: 21px !important; line-height: 1.3 !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f7;">

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#f5f5f7">
    <tr>
        <td class="outer" align="center" valign="top" style="padding:52px 16px;">

            <!-- Card -->
            <table role="presentation" class="card" cellpadding="0" cellspacing="0" border="0" width="560" bgcolor="#ffffff"
                   style="width:560px;max-width:100%;background-color:#ffffff;border-radius:18px;">

                <!-- Header -->
                <tr>
                    <td class="cell-header" style="padding:32px 44px 28px;border-bottom:1px solid #f2f2f2;">
                        <span style="font-family:-apple-system,BlinkMacSystemFont,'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:17px;font-weight:600;color:#1d1d1f;letter-spacing:-0.2px;mso-line-height-rule:exactly;line-height:1;">{{ config('app.name') }}</span>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td class="cell-body" style="padding:36px 44px 40px;">

                        <!-- Greeting -->
                        <p class="greeting" style="font-family:-apple-system,BlinkMacSystemFont,'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:24px;font-weight:600;color:#1d1d1f;letter-spacing:-0.4px;line-height:1.3;margin:0 0 14px 0;">{{ $greeting }}</p>

                        <!-- Intro -->
                        <p style="font-family:-apple-system,BlinkMacSystemFont,'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.65;color:#3d3d3f;margin:0 0 32px 0;">{{ $line }}</p>

                        <!-- Button -->
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:36px;">
                            <tr>
                                <td align="center">
                                    <!--[if mso]>
                                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $actionUrl }}"
                                        style="height:44px;v-text-anchor:middle;width:220px;"
                                        arcsize="50%" strokecolor="#1d1d1f" fillcolor="#1d1d1f">
                                        <v:textbox inset="0px,0px,0px,0px">
                                        <center style="font-family:Arial,sans-serif;font-size:14px;font-weight:bold;color:#ffffff;white-space:nowrap;">{{ $actionText }}</center>
                                        </v:textbox>
                                    </v:roundrect>
                                    <![endif]-->
                                    <!--[if !mso]><!-->
                                    <a href="{{ $actionUrl }}" target="_blank" rel="noopener"
                                       style="display:inline-block;background-color:#1d1d1f;color:#ffffff;text-decoration:none;font-family:-apple-system,BlinkMacSystemFont,'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;font-weight:500;line-height:1;padding:14px 30px;border-radius:980px;white-space:nowrap;mso-hide:all;">{{ $actionText }}</a>
                                    <!--<![endif]-->
                                </td>
                            </tr>
                        </table>

                        <!-- Fallback -->
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td style="border-top:1px solid #f2f2f2;padding-top:22px;">
                                    <p style="font-family:-apple-system,BlinkMacSystemFont,'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;color:#6e6e73;line-height:1.65;margin:0;">{{ __('mail.button_fallback') }} <a href="{{ $actionUrl }}" target="_blank" rel="noopener" style="color:#1d1d1f;text-decoration:none;word-break:break-all;">{{ $actionUrl }}</a></p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td class="cell-footer" style="padding:18px 44px 28px;border-top:1px solid #f2f2f2;">
                        <p style="font-family:-apple-system,BlinkMacSystemFont,'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;color:#6e6e73;line-height:1.6;margin:0 0 3px 0;">{{ __('mail.automated') }}</p>
                        <p style="font-family:-apple-system,BlinkMacSystemFont,'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;color:#6e6e73;line-height:1.6;margin:0;">© {{ date('Y') }} {{ config('app.name') }}. {{ __('mail.rights') }}</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
