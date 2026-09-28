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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('priority_id')->constrained('priorities')->cascadeOnDelete();
            $table->foreignId('status_id')->constrained('ticket_statuses')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->string('location')->nullable();
            $table->string('device_type')->nullable();
            $table->string('asset_number')->nullable();
            $table->string('contact_number')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('sla_paused_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('reopen_count')->default(0);
            $table->timestamp('overdue_notified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('ticket_number');
            $table->index('status_id');
            $table->index(['assigned_to', 'status_id']);
            $table->index('requester_id');
            $table->index('department_id');
            $table->index('category_id');
            $table->index('priority_id');
            $table->index('created_at');
            $table->index('due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
