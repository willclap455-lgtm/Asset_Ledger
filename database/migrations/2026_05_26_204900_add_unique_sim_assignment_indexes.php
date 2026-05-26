<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->clearDuplicateAssignments('phones');
        $this->clearDuplicateAssignments('modems');

        $phoneAssignedSimIds = DB::table('phones')
            ->whereNotNull('assigned_sim_card_id')
            ->pluck('assigned_sim_card_id');

        if ($phoneAssignedSimIds->isNotEmpty()) {
            DB::table('modems')
                ->whereIn('assigned_sim_card_id', $phoneAssignedSimIds)
                ->update(['assigned_sim_card_id' => null]);
        }

        Schema::table('phones', function (Blueprint $table): void {
            $table->unique('assigned_sim_card_id', 'phones_assigned_sim_card_id_unique');
        });

        Schema::table('modems', function (Blueprint $table): void {
            $table->unique('assigned_sim_card_id', 'modems_assigned_sim_card_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('modems', function (Blueprint $table): void {
            $table->dropUnique('modems_assigned_sim_card_id_unique');
        });

        Schema::table('phones', function (Blueprint $table): void {
            $table->dropUnique('phones_assigned_sim_card_id_unique');
        });
    }

    private function clearDuplicateAssignments(string $table): void
    {
        DB::table($table)
            ->whereNotNull('assigned_sim_card_id')
            ->select('assigned_sim_card_id')
            ->groupBy('assigned_sim_card_id')
            ->havingRaw('count(*) > 1')
            ->pluck('assigned_sim_card_id')
            ->each(function (string $simCardId) use ($table): void {
                $keepId = DB::table($table)
                    ->where('assigned_sim_card_id', $simCardId)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->value('id');

                DB::table($table)
                    ->where('assigned_sim_card_id', $simCardId)
                    ->where('id', '!=', $keepId)
                    ->update(['assigned_sim_card_id' => null]);
            });
    }
};
