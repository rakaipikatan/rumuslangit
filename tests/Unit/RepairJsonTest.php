<?php

namespace Tests\Unit;

use App\Services\GeminiService;
use PHPUnit\Framework\TestCase;

class RepairJsonTest extends TestCase
{
    public function test_trailing_comma_di_array_dan_objek_diperbaiki(): void
    {
        // Kasus nyata: Gemini mengembalikan koma sebelum "]" (profil karakter macet di loading).
        $rusak = "{\n \"a\": [\n  {\"x\": \"1\"},\n  {\"x\": \"2\"},\n ],\n \"b\": {\"k\": \"v\",},\n}";

        $this->assertNull(json_decode($rusak));
        $data = json_decode(GeminiService::repairJson($rusak), true);

        $this->assertSame('2', $data['a'][1]['x']);
        $this->assertSame('v', $data['b']['k']);
    }

    public function test_koma_di_dalam_string_tidak_dirusak(): void
    {
        $rusak = '{"teks": "satu, dua,]", "list": ["a",],}';
        $data  = json_decode(GeminiService::repairJson($rusak), true);

        $this->assertSame('satu, dua,]', $data['teks']);
        $this->assertSame(['a'], $data['list']);
    }

    public function test_newline_literal_di_dalam_string_tetap_diperbaiki(): void
    {
        $data = json_decode(GeminiService::repairJson("{\"t\": \"baris1\nbaris2\"}"), true);

        $this->assertSame("baris1\nbaris2", $data['t']);
    }

    public function test_json_valid_tidak_diubah(): void
    {
        $ok = '{"a":[1,2,3]}';
        $this->assertSame($ok, GeminiService::repairJson($ok));
    }
}
