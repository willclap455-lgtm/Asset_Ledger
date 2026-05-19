<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('asset_tag')->unique();
            $table->string('item_type')->index();
            $table->string('category')->nullable()->index();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('manufacturer')->nullable()->index();
            $table->string('model')->nullable()->index();
            $table->string('serial_number')->nullable()->unique();
            $table->string('status')->default('received')->index();
            $table->string('condition')->nullable()->index();
            $table->foreignUuid('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('location_id')->nullable()->constrained()->nullOnDelete();
            $table->date('received_at')->nullable()->index();
            $table->date('deployed_at')->nullable()->index();
            $table->date('retired_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();

            $table->index(['item_type', 'status']);
            $table->index(['client_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
