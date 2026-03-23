<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->json('filters');
            $table->string('frequency'); // daily | weekly | monthly
            $table->string('scheduled_time', 5); // HH:MM
            $table->tinyInteger('day_of_week')->nullable();  // 0 (Sun) – 6 (Sat), weekly only
            $table->tinyInteger('day_of_month')->nullable(); // 1 – 31, monthly only
            $table->boolean('include_new_only')->default(false);
            $table->string('email');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
        });
    }
};
