<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('channel')->default('email');
            $table->string('data_source')->default('orders');
            $table->string('status')->default('draft')->index();
            $table->string('date_scope_type')->default('last_30_days');
            $table->unsignedSmallInteger('date_scope_value')->nullable();
            $table->string('date_scope_unit')->nullable();
            $table->string('match_type')->default('all');
            $table->json('filters_json');
            $table->string('recipient_email');
            $table->unsignedSmallInteger('email_row_limit')->default(300);
            $table->string('frequency')->default('daily');
            $table->string('send_time', 5)->default('08:00');
            $table->tinyInteger('day_of_week')->nullable();
            $table->tinyInteger('day_of_month')->nullable();
            $table->timestamp('last_queued_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->unsignedInteger('last_result_count')->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'next_run_at']);
        });
    }
};
