<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Thêm membership_status: quản lý luồng join (pending|joined|rejected|left).
     * Giữ status: trạng thái hoạt động (active|inactive|suspended) khi đã joined.
     */
    public function up(): void
    {
        Schema::table('club_members', function (Blueprint $table) {
            if (!Schema::hasColumn('club_members', 'membership_status')) {
                $table->string('membership_status', 20)->default('joined')->after('status')
                    ->comment('pending|joined|rejected|left - luồng join');
            }
        });

        // Migrate dữ liệu cũ: status + left_at -> membership_status
        DB::table('club_members')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $membershipStatus = $this->mapMembershipStatus(
                    $row->status ?? 'active',
                    !empty($row->left_at),
                    !empty($row->rejection_reason)
                );
                DB::table('club_members')
                    ->where('id', $row->id)
                    ->update(['membership_status' => $membershipStatus]);
            }
        });

        Schema::table('club_members', function (Blueprint $table) {
            if (Schema::hasColumn('club_members', 'membership_status')) {
                $table->index(['club_id', 'membership_status'], 'club_members_club_id_membership_status_index');
            }
        });
    }

    private function mapMembershipStatus(string $status, bool $hasLeftAt, bool $hasRejectionReason): string
    {
        if ($status === 'pending') {
            return 'pending';
        }
        if ($status === 'active' || $status === 'suspended') {
            return 'joined';
        }
        // status === 'inactive'
        if ($hasLeftAt) {
            return 'left';
        }
        return 'rejected';
    }

    public function down(): void
    {
        Schema::table('club_members', function (Blueprint $table) {
            $table->dropIndex('club_members_club_id_membership_status_index');
        });
        Schema::table('club_members', function (Blueprint $table) {
            if (Schema::hasColumn('club_members', 'membership_status')) {
                $table->dropColumn('membership_status');
            }
        });
    }
};
