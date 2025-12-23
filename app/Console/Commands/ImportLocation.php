<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportLocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:provinces {date? : Ngày theo định dạng YYYY-MM-DD, mặc định là ngày hôm nay}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lấy danh sách tỉnh/thành phố theo ngày từ address-kit và import vào cơ sở dữ liệu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date') ?: now()->format('Y-m-d');
        $url = "https://production.cas.so/address-kit/{$date}/provinces";
        $this->info("Fetching data from: $url");
    
        try {
            $response = Http::get($url);
    
            if ($response->failed()) {
                $this->error("Failed to fetch data. HTTP status: " . $response->status());
                return 1;
            }
    
            $data = $response->json();
            $provinces = $data['provinces'] ?? null;
    
            if (!is_array($provinces)) {
                $this->error("Unexpected response format. Expecting 'provinces' array.");
                return 1;
            }
    
            foreach ($provinces as $item) {
                if (isset($item['name'])) {
                    Location::updateOrCreate(
                        ['name' => $item['name']],
                        []
                    );
    
                    $this->info("Imported/Updated: {$item['name']}");
                } else {
                    $this->warn("Skipping invalid item: " . json_encode($item));
                }
            }
    
            $this->info("Import completed successfully.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Exception occurred: " . $e->getMessage());
            return 1;
        }
    }
    
}
