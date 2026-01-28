<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('club_members', function (Blueprint $table) {
            if (!Schema::hasColumn('club_members', 'role')) {
                $table->enum('role', ['member', 'admin', 'manager', 'treasurer', 'secretary'])->default('member')->after('user_id');
            }
            
            if (!Schema::hasColumn('club_members', 'position')) {
                $table->string('position')->nullable()->comment('Chức vụ trong CLB')->after('role');
            }
            
            if (!Schema::hasColumn('club_members', 'status')) {
                $table->enum('status', ['pending', 'active', 'inactive', 'suspended'])->default('active')->comment('pending = join request chưa duyệt')->after('position');
            }
            
            if (!Schema::hasColumn('club_members', 'message')) {
                $table->text('message')->nullable()->comment('Lời nhắn từ join request')->after('status');
            }
            
            if (!Schema::hasColumn('club_members', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->comment('Người duyệt join request')->after('message');
            }
            
            if (!Schema::hasColumn('club_members', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            
            if (!Schema::hasColumn('club_members', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->comment('Lý do từ chối')->after('reviewed_at');
            }
            
            if (!Schema::hasColumn('club_members', 'left_at')) {
                $table->timestamp('left_at')->nullable()->after('joined_at');
            }
            
            if (!Schema::hasColumn('club_members', 'notes')) {
                $table->text('notes')->nullable()->after('left_at');
            }
        });
        
        // Add indexes (will fail silently if already exist)
        try {
            Schema::table('club_members', function (Blueprint $table) {
                $table->index(['club_id', 'status'], 'club_members_club_id_status_index');
            });
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        try {
            Schema::table('club_members', function (Blueprint $table) {
                $table->index(['club_id', 'role'], 'club_members_club_id_role_index');
            });
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        // Add foreign key for reviewed_by
        try {
            Schema::table('club_members', function (Blueprint $table) {
                if (Schema::hasColumn('club_members', 'reviewed_by')) {
                    $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        } catch (\Exception $e) {
            // Foreign key might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_members', function (Blueprint $table) {
            if (Schema::hasColumn('club_members', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('club_members', 'left_at')) {
                $table->dropColumn('left_at');
            }
            if (Schema::hasColumn('club_members', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
            if (Schema::hasColumn('club_members', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
            if (Schema::hasColumn('club_members', 'reviewed_by')) {
                $table->dropForeign(['reviewed_by']);
                $table->dropColumn('reviewed_by');
            }
            if (Schema::hasColumn('club_members', 'message')) {
                $table->dropColumn('message');
            }
            if (Schema::hasColumn('club_members', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('club_members', 'position')) {
                $table->dropColumn('position');
            }
            if (Schema::hasColumn('club_members', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
