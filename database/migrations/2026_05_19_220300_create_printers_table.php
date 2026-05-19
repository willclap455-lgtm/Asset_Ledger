<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('printer_identifier')->nullable()->unique();
            $table->string('printer_color')->nullable();
            $table->string('firmware_version')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printers');
    }
};
