<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('club_profiles', function (Blueprint $table) {
            $table->string('zalo_link')->nullable()->after('longitude');
            $table->boolean('zalo_enabled')->default(false)->after('zalo_link');
            $table->string('qr_zalo')->nullable()->after('zalo_enabled');
        });

        $this->migrateZaloFromJson();
    }

    /**
     * Copy existing Zalo data from social_links/settings to new columns.
     */
    private function migrateZaloFromJson(): void
    {
        $profiles = DB::table('club_profiles')->get();

        foreach ($profiles as $row) {
            $updates = [];
            $socialLinks = $row->social_links ? json_decode($row->social_links, true) : null;
            $settings = $row->settings ? json_decode($row->settings, true) : null;

            if (is_array($socialLinks) && isset($socialLinks['zalo'])) {
                $updates['zalo_link'] = $socialLinks['zalo'];
            }
            if (is_array($settings) && isset($settings['zalo_enabled'])) {
                $updates['zalo_enabled'] = (bool) $settings['zalo_enabled'];
            }

            if (!empty($updates)) {
                DB::table('club_profiles')->where('id', $row->id)->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_profiles', function (Blueprint $table) {
            $table->dropColumn(['zalo_link', 'zalo_enabled', 'qr_zalo']);
        });
    }
};
