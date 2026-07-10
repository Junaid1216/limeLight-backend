<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
             $table->unsignedBigInteger('invoice_id')->unique();

            $table->string('shop_id')->nullable();
            $table->string('shop_name')->nullable();

            $table->string('sale_from_date')->nullable();
            $table->string('sale_to_date')->nullable();
            $table->string('date')->nullable();

            $table->string('coupon_no')->nullable();

            $table->string('customer_name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('gender')->nullable();

            $table->decimal('net_total', 12, 2)->default(0);

            $table->text('comments')->nullable();
            $table->text('additional_comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales');
    }
}
