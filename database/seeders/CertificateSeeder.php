<?php

namespace Database\Seeders;

use App\Models\Certificate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CertificateSeeder extends Seeder
{
    private const PUBLIC_STORAGE_PREFIX = '/storage/';

    public function run(): void
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory('certificates');

        $sources = [
            public_path('WhatsApp Image 2026-05-15 at 16.30.18.jpeg'),
            public_path('WhatsApp Image 2026-05-15 at 16.30.34.jpeg'),
        ];

        $rows = [
            ['name' => 'CNA', 'file' => 'cna.jpg', 'source_index' => 0, 'sort_order' => 0],
            ['name' => 'CNN', 'file' => 'CNN.png', 'source_index' => 1, 'sort_order' => 1],
            ['name' => 'BBC', 'file' => 'BBC.png', 'source_index' => 0, 'sort_order' => 2],
        ];

        foreach ($rows as $row) {

            Certificate::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'image' => env('APP_URL', '').'/storage/certificates/'.$row['file'],
                    'description' => null,
                    'sort_order' => $row['sort_order'],
                ]
            );
        }
    }
}
