<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repairs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open')->index();
            $table->text('issue_description');
            $table->text('repair_performed')->nullable();
            $table->timestamp('opened_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->text('resolution_details')->nullable();
            $table->timestamps();

            $table->index(['inventory_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
