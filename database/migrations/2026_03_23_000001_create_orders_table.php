<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->nullable()->index();
            $table->string('ordernumber')->nullable();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('product_description')->nullable();
            $table->string('pipcode')->nullable()->index();
            $table->string('supplier_id')->nullable()->index();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('approved_qty', 10, 2)->nullable();
            $table->decimal('price', 10, 4)->nullable();
            $table->decimal('max_price', 10, 4)->nullable();
            $table->decimal('dt_price', 10, 4)->nullable();
            $table->decimal('rule_price', 10, 4)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('sent_date')->nullable();
            $table->boolean('is_opened')->default(false);
            $table->boolean('is_transmitted')->default(false);
            $table->string('transmit_method')->nullable();
            $table->timestamp('transmit_date')->nullable();
            $table->timestamp('orderdate')->nullable()->index();
            $table->string('response')->nullable();
            $table->string('category')->nullable();
            $table->string('price_range')->nullable();
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->string('flag')->nullable();
            $table->string('stock_status')->nullable();
            $table->timestamps();
        });
    }
};
