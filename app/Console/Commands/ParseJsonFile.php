<?php

namespace App\Console\Commands;

use App\Models\CompetitionLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Jobs\DownloadImageJob;

class ParseJsonFile extends Command
{
    protected $signature = 'json:parse {file}';
    protected $description = 'Đọc JSON từ file, parse và import vào database';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        set_time_limit(0); // tránh timeout PHP

        $filePath = base_path($this->argument('file'));
        if (!file_exists($filePath)) {
            $this->error("File không tồn tại: {$filePath}");
            return 1;
        }

        $raw = file_get_contents($filePath);
        $firstDecode = json_decode($raw, true);
        $data = is_string($firstDecode) ? json_decode($firstDecode, true) : $firstDecode;

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Lỗi parse JSON: " . json_last_error_msg());
            return 1;
        }

        if (!isset($data['branches'])) {
            $this->error("Không tìm thấy key 'branches'");
            return 1;
        }

        // Reset dữ liệu cũ
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CompetitionLocation::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Storage::disk('public')->deleteDirectory('competition_locations');
        Storage::disk('public')->makeDirectory('competition_locations');

        $importData = [];
        $count = count($data['branches']);
        $this->info("Bắt đầu import {$count} bản ghi...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($data['branches'] as $branch) {
            $importData[] = [
                'name' => $branch['name'] ?? null,
                'address' => $branch['address'] ?? null,
                'image' => null, // để queue tải ảnh
                'phone' => $branch['phone'] ?? null,
                'opening_time' => isset($branch['morningStartWorkingTime'])
                    ? $this->decimalHourToTime($branch['morningStartWorkingTime'])
                    : null,
                'closing_time' => isset($branch['afternoonEndWorkingTime'])
                    ? $this->decimalHourToTime($branch['afternoonEndWorkingTime'])
                    : null,

                'latitude' => $branch['location']['_latitude'] ?? null,
                'longitude' => $branch['location']['_longitude'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
                'avatar_url' => $branch['avatar'] ?? null,
            ];
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Chia batch insert 200 bản ghi
        $batchSize = 200;
        foreach (array_chunk($importData, $batchSize) as $chunk) {
            CompetitionLocation::insert($chunk);
        }

        $this->info("✔ Đã insert {$count} bản ghi thành công!");

        // Queue tải ảnh
        $this->info("Đang tạo queue tải ảnh...");
        foreach (CompetitionLocation::whereNotNull('avatar_url')->cursor() as $location) {
            DownloadImageJob::dispatch($location->id, $location->avatar_url);
        }

        $this->info("✔ Tạo queue tải ảnh thành công. Chạy `php artisan queue:work` để tải ảnh.");

        return 0;
    }

    function decimalHourToTime($decimalHour)
    {
        if ($decimalHour === null)
            return null;

        $hours = floor($decimalHour);
        $minutes = round(($decimalHour - $hours) * 60);

        return sprintf('%02d:%02d:00', $hours, $minutes);
    }

}
