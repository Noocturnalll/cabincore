<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evidence_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('sender_phone')->nullable()->index();
            $table->string('sender_name')->nullable();
            $table->string('group_jid')->nullable()->index();
            $table->string('source_channel')->default('whatsapp');
            $table->string('tc_type')->nullable()->index();
            $table->string('doc_type')->nullable()->index();
            $table->string('doc_no')->nullable()->index();
            $table->string('filename');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('cloud_storage')->default('onedrive');
            $table->text('cloud_url')->nullable();
            $table->text('cloud_path')->nullable();
            $table->text('local_path')->nullable();
            $table->text('raw_caption')->nullable();
            $table->string('matched_model_type')->nullable();
            $table->unsignedBigInteger('matched_model_id')->nullable();
            $table->string('status')->default('saved');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['matched_model_type', 'matched_model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence_submissions');
    }
};
