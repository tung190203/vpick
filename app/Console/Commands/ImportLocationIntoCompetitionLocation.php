<?php

namespace App\Console\Commands;

use App\Models\CompetitionLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLocationIntoCompetitionLocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-location-into-competition-location';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $sportId = 1; // sport cố định
    
            $locations = CompetitionLocation::all();
    
            if ($locations->isEmpty()) {
                $this->info("Không có CompetitionLocation nào!");
                return 0;
            }
    
            // ⚠️ Xóa sạch bảng trước khi insert
            DB::table('competition_location_sport')->truncate();
    
            $now = now();
            $data = [];
    
            foreach ($locations as $item) {
                $data[] = [
                    'competition_location_id' => $item->id,
                    'sport_id' => $sportId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
    
            // Insert bulk cho cực nhanh
            DB::table('competition_location_sport')->insert($data);
    
            $this->info("Đã nhập mới toàn bộ competition_location_sport với sport_id = {$sportId}");
            return 0;
    
        } catch (\Exception $e) {
            $this->error("Exception occurred: " . $e->getMessage());
            return 1;
        }
    }       
}
