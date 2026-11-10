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
}
