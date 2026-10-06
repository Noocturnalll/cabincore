<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ims_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type'); // out|in|adjustment|transfer|repair_in
            $table->string('status'); // draft|pending_approval|approved|rejected|cancelled|completed
            $table->string('usage_type')->nullable(); // consume|loan
            $table->date('expected_return_date')->nullable();
            $table->text('purpose_description');
            $table->string('reference_no')->nullable();
            $table->string('aircraft_registration')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('divisions');
            $table->foreignId('supplier_id')->nullable()->constrained('ims_suppliers');
            $table->string('source')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->dateTime('requested_at');
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->string('picked_up_by_name')->nullable();
            $table->dateTime('picked_up_at')->nullable();
            $table->text('handover_note')->nullable();
            $table->foreignId('handed_over_by')->nullable()->constrained('users');
            $table->foreignId('parent_transaction_id')->nullable()->constrained('ims_transactions');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ims_transactions'); }
};