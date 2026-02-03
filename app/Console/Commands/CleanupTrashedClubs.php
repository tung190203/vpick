<?php

namespace App\Console\Commands;

use App\Models\Club\Club;
use App\Services\ImageOptimizationService;
use Illuminate\Console\Command;

class CleanupTrashedClubs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clubs:cleanup-trashed {--days=30 : Number of days to keep trashed clubs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dọn dẹp các club đã bị soft delete quá số ngày quy định';

    protected ImageOptimizationService $imageService;

    public function __construct(ImageOptimizationService $imageService)
    {
        parent::__construct();
        $this->imageService = $imageService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("🔍 Tìm kiếm clubs đã xóa trước ngày: {$cutoffDate->toDateString()}...");

        $clubs = Club::onlyTrashed()
            ->with('profile')
            ->where('deleted_at', '<=', $cutoffDate)
            ->get();

        if ($clubs->isEmpty()) {
            $this->info("✅ Không có club nào đã xóa quá {$days} ngày");
            return 0;
        }

        $this->info("📋 Tìm thấy {$clubs->count()} clubs cần dọn dẹp");

        if (!$this->confirm('Bạn có chắc chắn muốn xóa vĩnh viễn các clubs này?', true)) {
            $this->info('❌ Đã hủy thao tác');
            return 0;
        }

        $progressBar = $this->output->createProgressBar($clubs->count());
        $progressBar->start();

        $deletedCount = 0;
        $errors = [];

        foreach ($clubs as $club) {
            try {
                // Xóa logo
                $logoPath = $club->getRawOriginal('logo_url');
                if ($logoPath) {
                    $this->imageService->deleteOldImage($logoPath);
                }

                // Xóa cover image
                if ($club->profile) {
                    $coverPath = $club->profile->getRawCoverImagePath();
                    if ($coverPath) {
                        $this->imageService->deleteOldImage($coverPath);
                    }
                }

                $clubName = $club->name;
                $club->forceDelete();
                $deletedCount++;

                $progressBar->advance();
            } catch (\Exception $e) {
                $errors[] = [
                    'club_id' => $club->id,
                    'club_name' => $club->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Đã xóa vĩnh viễn {$deletedCount}/{$clubs->count()} clubs");

        if (!empty($errors)) {
            $this->error("⚠️  Có " . count($errors) . " lỗi xảy ra:");
            $this->table(
                ['Club ID', 'Tên Club', 'Lỗi'],
                array_map(fn($err) => [
                    $err['club_id'],
                    $err['club_name'],
                    $err['error']
                ], $errors)
            );
        }

        $this->newLine();
        $this->info("📊 Thống kê:");
        $this->info("   - Số ngày giữ lại: {$days} ngày");
        $this->info("   - Ngày cut-off: {$cutoffDate->toDateString()}");
        $this->info("   - Đã xóa: {$deletedCount} clubs");
        $this->info("   - Lỗi: " . count($errors));

        return 0;
    }
}
