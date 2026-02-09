<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("
                UPDATE club_wallet_transactions t
                INNER JOIN club_expenses e ON t.source_id = e.id AND t.source_type = 'expense'
                SET t.description = e.title
                WHERE t.description IS NULL AND e.title IS NOT NULL AND e.title != ''
            ");
        } else {
            $ids = DB::table('club_wallet_transactions')
                ->where('source_type', 'expense')
                ->whereNull('description')
                ->pluck('source_id', 'id');
            foreach ($ids as $txId => $expenseId) {
                $title = DB::table('club_expenses')->where('id', $expenseId)->value('title');
                if ($title !== null && $title !== '') {
                    DB::table('club_wallet_transactions')->where('id', $txId)->update(['description' => $title]);
                }
            }
        }
    }

    public function down(): void
    {
        // Không revert (không xóa description đã điền)
    }
};
