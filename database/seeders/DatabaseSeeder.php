<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@ocr.local'],
            [
                'name' => 'OCR Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );

        User::factory()->count(5)->create();

        $settings = [
            ['group' => 'ocr', 'key' => 'ocr.timeout', 'type' => 'integer', 'value' => '360', 'description' => 'OCR timeout in seconds.'],
            ['group' => 'ocr', 'key' => 'ocr.retry', 'type' => 'integer', 'value' => '3', 'description' => 'Total retries per OCR job.'],
            ['group' => 'ocr', 'key' => 'ocr.min_confidence', 'type' => 'float', 'value' => '75', 'description' => 'Minimum confidence for auto approval.'],
            ['group' => 'upload', 'key' => 'upload.max_mb', 'type' => 'integer', 'value' => '50', 'description' => 'Maximum upload size per file.'],
            ['group' => 'ui', 'key' => 'ui.default_theme', 'type' => 'string', 'value' => 'system', 'description' => 'Default UI theme.'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                array_merge($setting, ['updated_by' => $admin->id]),
            );
        }
    }
}
