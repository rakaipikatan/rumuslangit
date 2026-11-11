<?php

namespace Tests\Feature;

use Tests\TestCase;

class HalamanLegalTest extends TestCase
{
    public function test_tiga_halaman_legal_tampil_dua_bahasa_tanpa_email_kontak(): void
    {
        foreach (['id', 'en'] as $bahasa) {
            foreach (['privasi', 'syarat', 'refund'] as $h) {
                $html = $this->withSession(['locale' => $bahasa])->withHeader('Accept-Language', $bahasa)->get('/' . $h)->assertOk()->getContent();
                // Tidak boleh ada alamat email kontak di halaman legal (chat adalah satu-satunya saluran)
                $badan = preg_replace('/<script.*?<\/script>/s', '', $html);
                $this->assertSame(0, preg_match('/mailto:|[\w.+-]+@[\w-]+\.[\w.]+/', $badan), "$h/$bahasa memuat email");
            }
        }
    }

    public function test_kebijakan_refund_menyatakan_tidak_ada_pengembalian_dan_footer_menautkan_halaman(): void
    {
        $this->get('/refund')->assertOk()->assertSee('Tidak ada pengembalian dana');
        $beranda = $this->get('/')->assertOk();
        foreach (['/privasi', '/syarat', '/refund'] as $url) {
            $beranda->assertSee($url, false);
        }
    }
}
