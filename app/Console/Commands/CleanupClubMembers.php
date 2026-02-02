<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Club\ClubMember;

class CleanupClubMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'club:cleanup-members {--dry-run : Chỉ hiển thị không xóa}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa các club members có user_id không tồn tại (orphaned records)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('🔍 Đang tìm các club members có user bị xóa...');

        // Tìm các club_members có user_id không tồn tại trong bảng users
        $orphanedMembers = DB::table('club_members')
            ->leftJoin('users', 'club_members.user_id', '=', 'users.id')
            ->whereNull('users.id')
            ->select('club_members.*')
            ->get();

        $count = $orphanedMembers->count();

        if ($count === 0) {
            $this->info('✅ Không tìm thấy dữ liệu cần clean up!');
            return 0;
        }

        $this->warn("⚠️  Tìm thấy {$count} club members bị orphaned:");

        // Hiển thị chi tiết
        $table = [];
        foreach ($orphanedMembers as $member) {
            $table[] = [
                'ID' => $member->id,
                'Club ID' => $member->club_id,
                'User ID (deleted)' => $member->user_id,
                'Role' => $member->role,
                'Status' => $member->membership_status,
            ];
        }

        $this->table(
            ['ID', 'Club ID', 'User ID (deleted)', 'Role', 'Status'],
            $table
        );

        if ($isDryRun) {
            $this->info('🔍 DRY RUN mode: Không có gì bị xóa.');
            $this->info('💡 Chạy lại không có --dry-run để thực hiện xóa.');
            return 0;
        }

        // Xác nhận trước khi xóa
        if (!$this->confirm("Bạn có chắc muốn xóa {$count} records này?")) {
            $this->info('❌ Đã hủy.');
            return 0;
        }

        // Xóa các orphaned members
        $deleted = DB::table('club_members')
            ->leftJoin('users', 'club_members.user_id', '=', 'users.id')
            ->whereNull('users.id')
            ->delete();

        $this->info("✅ Đã xóa {$deleted} club members bị orphaned!");

        return 0;
    }
}
