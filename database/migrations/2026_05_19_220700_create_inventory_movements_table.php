<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('movement_number')->unique();
            $table->string('movement_type')->index();
            $table->timestamp('occurred_at')->index();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['movement_type', 'occurred_at']);
            $table->index(['client_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
