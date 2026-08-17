<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kode Verifikasi Rumus Langit</title>
</head>
<body style="margin:0;padding:0;background:#060614;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#060614;padding:40px 20px;">
  <tr>
    <td align="center">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;">

        {{-- Header --}}
        <tr>
          <td align="center" style="padding-bottom:24px;">
            <p style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">
              ✦ Rumus Langit
            </p>
          </td>
        </tr>

        {{-- Card --}}
        <tr>
          <td style="background:#0d0d2b;border:1px solid rgba(124,58,237,0.3);border-radius:16px;padding:36px 32px;">

            <p style="margin:0 0 8px;font-size:18px;font-weight:700;color:#ffffff;">
              Kode Verifikasi Anda
            </p>
            <p style="margin:0 0 28px;font-size:14px;color:rgba(255,255,255,0.45);line-height:1.6;">
              Masukkan kode berikut untuk memverifikasi email Anda dan mengakses analisis personal.
            </p>

            {{-- OTP Box --}}
            <div style="background:rgba(124,58,237,0.12);border:1px solid rgba(124,58,237,0.4);border-radius:12px;padding:20px;text-align:center;margin-bottom:28px;">
              <p style="margin:0;font-size:38px;font-weight:800;letter-spacing:10px;color:#a78bfa;font-family:monospace;">
                {{ $otp }}
              </p>
            </div>

            <p style="margin:0 0 20px;font-size:13px;color:rgba(255,255,255,0.35);line-height:1.6;">
              ⏱ Kode berlaku selama <strong style="color:rgba(255,255,255,0.6);">5 menit</strong>.<br>
              🔒 Jangan bagikan kode ini kepada siapapun.
            </p>

            <hr style="border:none;border-top:1px solid rgba(255,255,255,0.06);margin:0 0 20px;">

            <p style="margin:0;font-size:12px;color:rgba(255,255,255,0.25);line-height:1.6;">
              Jika Anda tidak merasa meminta kode ini, abaikan email ini. Akun Anda tetap aman.
            </p>

          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td align="center" style="padding-top:24px;">
            <p style="margin:0;font-size:12px;color:rgba(255,255,255,0.2);">
              © {{ date('Y') }} Rumus Langit · Platform Konsultasi Metafisika
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
