<?php

namespace Tests\Unit;

use App\Mail\OtpMail;
use Tests\TestCase;

class OtpMailTest extends TestCase
{
    public function test_email_otp_memuat_versi_html_dan_teks_dengan_kode(): void
    {
        $mail = new OtpMail('123456');

        $mail->assertSeeInHtml('123456');
        $mail->assertSeeInText('123456');
        $this->assertStringContainsString('123456', $mail->envelope()->subject);
    }

    public function test_email_otp_mengingatkan_cek_folder_spam(): void
    {
        $mail = new OtpMail('123456');

        $mail->assertSeeInHtml('folder Spam atau Junk');
        $mail->assertSeeInHtml('Bukan spam');
        $mail->assertSeeInText('folder Spam atau Junk');
    }

    public function test_email_hoki_harian_mengingatkan_cek_folder_spam(): void
    {
        $mail = new \App\Mail\HokiHarianMail('Budi', ['angka' => [1, 2, 3], 'warna' => ['Merah', 'Biru'], 'mantra' => 'Semangat']);

        $mail->assertSeeInHtml('folder Spam atau Junk');
    }
}
