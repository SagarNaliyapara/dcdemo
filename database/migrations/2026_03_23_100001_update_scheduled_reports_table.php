<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_reports', function (Blueprint $table) {
            $table->renameColumn('scheduled_time', 'send_time');
            $table->renameColumn('filters', 'filters_json');
            $table->dropColumn('include_new_only');
        });
    }
};
