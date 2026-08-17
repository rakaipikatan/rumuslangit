<?php

namespace Database\Seeders;

use App\Models\BirthProfile;
use App\Models\Subscription;
use App\Models\User;
use App\Services\KalkulasiEngine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            [
                'name'           => 'Yohanes',
                'jenis_kelamin'  => 'Laki-laki',
                'email'          => 'yohanes.slk@gmail.com',
                'password'       => 'RumusLangit@2025!',
                'dob'            => '1990-01-01',
                'birth_hour'     => 7,
                'province'       => 'DKI Jakarta',
                'kota'           => 'Jakarta Selatan',
                'kecamatan'      => 'Kebayoran Baru',
                'kelurahan'      => 'Senayan',
            ],
            [
                'name'           => 'Demo Rumus Langit',
                'jenis_kelamin'  => 'Laki-laki',
                'email'          => 'demo@rumuslangit.biz.id',
                'password'       => 'DemoAccess@2025!',
                'dob'            => '1995-06-15',
                'birth_hour'     => 10,
                'province'       => 'Jawa Barat',
                'kota'           => 'Bandung',
                'kecamatan'      => 'Coblong',
                'kelurahan'      => 'Dago',
            ],
        ];

        foreach ($akun as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'                => $data['name'],
                    'jenis_kelamin'       => $data['jenis_kelamin'],
                    'password'            => Hash::make($data['password']),
                    'dob'                 => $data['dob'],
                    'birth_hour'          => $data['birth_hour'],
                    'province'            => $data['province'],
                    'kota'                => $data['kota'],
                    'kecamatan'           => $data['kecamatan'],
                    'kelurahan'           => $data['kelurahan'],
                    'subscription_status' => 'active',
                    'email_verified_at'   => now(),
                ]
            );

            // Birth profile — hitung real dari dob
            $k = KalkulasiEngine::hitung($data['dob'], $data['birth_hour']);
            BirthProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'weton_neptune' => $k['weton']['neptu'],
                    'shio'          => $k['shio']['nama'],
                    'zodiac_sign'   => $k['zodiak'],
                    'element'       => $k['elemen'],
                    'calculated_at' => now(),
                ]
            );

            // Subscription aktif 10 tahun ke depan (full access permanen untuk testing)
            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan'          => 'yearly',
                    'starts_at'     => now(),
                    'ends_at'       => now()->addYears(10),
                    'auto_renew'    => false,
                    'gateway_token' => null,
                ]
            );

            $this->command->info("✓ Akun dibuat: {$data['email']} | password: {$data['password']}");
        }
    }
}
