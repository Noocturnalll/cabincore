<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;

class AirportSeeder extends Seeder
{
    public function run(): void
    {
        $airports = [
            ['kode' => 'CGK', 'nama' => 'Soekarno-Hatta International Airport', 'kota' => 'Tangerang / Jakarta', 'status' => 'Aktif'],
            ['kode' => 'HLP', 'nama' => 'Halim Perdanakusuma International Airport', 'kota' => 'Jakarta', 'status' => 'Aktif'],
            ['kode' => 'SUB', 'nama' => 'Juanda International Airport', 'kota' => 'Surabaya', 'status' => 'Aktif'],
            ['kode' => 'KNO', 'nama' => 'Kualanamu International Airport', 'kota' => 'Medan', 'status' => 'Aktif'],
            ['kode' => 'UPG', 'nama' => 'Sultan Hasanuddin International Airport', 'kota' => 'Makassar', 'status' => 'Aktif'],
            ['kode' => 'MDC', 'nama' => 'Sam Ratulangi International Airport', 'kota' => 'Manado', 'status' => 'Aktif'],
            ['kode' => 'BPN', 'nama' => 'Sepinggan International Airport', 'kota' => 'Balikpapan', 'status' => 'Aktif'],
            ['kode' => 'AMQ', 'nama' => 'Pattimura International Airport', 'kota' => 'Ambon', 'status' => 'Aktif'],
            ['kode' => 'SRG', 'nama' => 'Ahmad Yani International Airport', 'kota' => 'Semarang', 'status' => 'Aktif'],
            ['kode' => 'PLM', 'nama' => 'Sultan Mahmud Badaruddin II', 'kota' => 'Palembang', 'status' => 'Aktif'],
            ['kode' => 'DPS', 'nama' => 'Ngurah Rai International Airport', 'kota' => 'Denpasar', 'status' => 'Aktif'],
            ['kode' => 'KOE', 'nama' => 'El Tari International Airport', 'kota' => 'Kupang', 'status' => 'Aktif'],
            ['kode' => 'LOP', 'nama' => 'Zainuddin Abdul Madjid International Airport', 'kota' => 'Lombok', 'status' => 'Aktif'],
            ['kode' => 'SOC', 'nama' => 'Adi Soemarmo International Airport', 'kota' => 'Surakarta', 'status' => 'Aktif'],
            ['kode' => 'PDG', 'nama' => 'Minangkabau International Airport', 'kota' => 'Padang', 'status' => 'Aktif'],
            ['kode' => 'BTH', 'nama' => 'Hang Nadim International Airport', 'kota' => 'Batam', 'status' => 'Aktif'],
            ['kode' => 'PKU', 'nama' => 'Sultan Syarif Kasim II', 'kota' => 'Pekanbaru', 'status' => 'Aktif'],
            ['kode' => 'YIA', 'nama' => 'Yogyakarta International Airport', 'kota' => 'Yogyakarta', 'status' => 'Aktif'],
        ];

        foreach ($airports as $airport) {
            Airport::updateOrCreate(
                ['kode' => $airport['kode']],
                $airport
            );
        }
    }
}
