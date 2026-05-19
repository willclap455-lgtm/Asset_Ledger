<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sim_cards', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('iccid')->unique();
            $table->string('imsi')->nullable()->unique();
            $table->string('carrier')->nullable()->index();
            $table->string('associated_phone_number')->nullable()->index();
            $table->foreignUuid('assigned_inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('activation_status')->default('inactive')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_cards');
    }
};
