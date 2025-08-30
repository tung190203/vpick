<?php

namespace App\Jobs;

use App\Models\CompetitionLocation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DownloadImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $locationId;
    public $avatarUrl;

    public function __construct(int $locationId, ?string $avatarUrl)
    {
        $this->locationId = $locationId;
        $this->avatarUrl = $avatarUrl;
    }

    public function handle()
    {
        if (!$this->avatarUrl) return;

        try {
            $imgContent = Http::get($this->avatarUrl)->body();
            $fileName = Str::uuid() . '.jpg';
            $path = 'competition_locations/' . $fileName;
            Storage::disk('public')->put($path, $imgContent);

            CompetitionLocation::where('id', $this->locationId)
                ->update(['image' => 'storage/' . $path]);
        } catch (\Exception $e) {
            Log::error("Lỗi tải ảnh cho location ID {$this->locationId}: " . $e->getMessage());
        }
    }
}
