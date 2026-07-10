<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');

            $table->foreign('invoice_id')
                ->references('invoice_id')
                ->on('sales')
                ->onDelete('cascade');

            $table->string('product_code')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_category')->nullable();
            $table->string('product_sub_category')->nullable();
            $table->string('size')->nullable();
            $table->string('technical_details')->nullable();
            $table->string('color')->nullable();

            $table->integer('quantity')->default(0);

            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);

            $table->string('salesperson_name')->nullable();
            $table->string('salesperson_code')->nullable();

            $table->string('category')->nullable();
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
        Schema::dropIfExists('sale_items');
    }
}
