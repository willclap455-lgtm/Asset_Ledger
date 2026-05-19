<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone_number')->nullable()->index();
            $table->string('carrier')->nullable()->index();
            $table->string('imei')->nullable()->unique();
            $table->string('android_version')->nullable()->index();
            $table->foreignUuid('assigned_sim_card_id')->nullable()->constrained('sim_cards')->nullOnDelete();
            $table->foreignUuid('assigned_printer_id')->nullable()->constrained('printers')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phones');
    }
};
