<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modems', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('imei')->nullable()->unique();
            $table->string('carrier')->nullable()->index();
            $table->foreignUuid('assigned_sim_card_id')->nullable()->constrained('sim_cards')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modems');
    }
};
