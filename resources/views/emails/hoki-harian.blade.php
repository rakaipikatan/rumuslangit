<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Peta Hoki Harian</title></head>
<body style="margin:0;padding:0;background:#060614;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#060614;padding:40px 20px;">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;">
      <tr><td align="center" style="padding-bottom:20px;">
        <p style="margin:0;font-size:20px;font-weight:700;color:#fff;">✦ Rumus Langit</p>
        <p style="margin:4px 0 0;font-size:11px;color:rgba(255,255,255,0.3);letter-spacing:2px;">PETA HOKI HARIAN</p>
      </td></tr>
      <tr><td style="background:#0d0d2b;border:1px solid rgba(124,58,237,0.3);border-radius:16px;padding:32px;">
        <p style="margin:0 0 4px;font-size:13px;color:rgba(255,255,255,0.4);">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
        <p style="margin:0 0 24px;font-size:18px;font-weight:700;color:#fff;">Halo, {{ $namaUser }}! 🌟</p>

        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
          <tr>
            <td width="48%" style="background:rgba(124,58,237,0.12);border:1px solid rgba(124,58,237,0.3);border-radius:12px;padding:16px;text-align:center;">
              <p style="margin:0 0 6px;font-size:11px;color:rgba(255,255,255,0.4);letter-spacing:1px;">🔢 ANGKA HOKI</p>
              <p style="margin:0;font-size:26px;font-weight:800;color:#a78bfa;letter-spacing:6px;">{{ implode(' · ', $hoki['angka']) }}</p>
            </td>
            <td width="4%"></td>
            <td width="48%" style="background:rgba(34,211,238,0.08);border:1px solid rgba(34,211,238,0.25);border-radius:12px;padding:16px;text-align:center;">
              <p style="margin:0 0 6px;font-size:11px;color:rgba(255,255,255,0.4);letter-spacing:1px;">🎨 WARNA BUSANA</p>
              <p style="margin:0;font-size:15px;font-weight:700;color:#67e8f9;">{{ implode(' &amp; ', $hoki['warna']) }}</p>
            </td>
          </tr>
        </table>

        <div style="background:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.2);border-radius:10px;padding:14px;margin-bottom:20px;">
          <p style="margin:0;font-size:13px;color:#fcd34d;font-style:italic;line-height:1.6;">💫 {{ $hoki['mantra'] }}</p>
        </div>

        <hr style="border:none;border-top:1px solid rgba(255,255,255,0.06);margin:0 0 16px;">
        <p style="margin:0;font-size:11px;color:rgba(255,255,255,0.25);text-align:center;">
          Rumus Langit · Platform Konsultasi Metafisika
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
