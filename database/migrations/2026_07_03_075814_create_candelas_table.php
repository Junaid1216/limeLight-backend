<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCandelasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('candelas', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->string('branch_id')->nullable();
            $table->string('date_and_time')->nullable();
            $table->string('sales_boy_name')->nullable();
            $table->string('sales_boy_code')->nullable();
            $table->string('item_code')->nullable();
            $table->string('item_name')->nullable();
            $table->string('quantity')->nullable();
            $table->string('category')->nullable();
            $table->string('amount')->nullable();
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
        Schema::dropIfExists('candelas');
    }
}
