<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movement_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_movement_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('previous_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('new_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('previous_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUuid('new_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->json('item_snapshot');
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();

            $table->index(['inventory_item_id', 'created_at']);
            $table->index(['inventory_movement_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movement_lines');
    }
};
