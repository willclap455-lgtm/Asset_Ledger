<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_movement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('document_type')->index();
            $table->string('template_key')->default('standard_movement');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('checksum', 128)->nullable();
            $table->timestamp('generated_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
