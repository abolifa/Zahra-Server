<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('color_code');
            $table->string('color')->nullable()->comment('Color of the variant');
            $table->integer('status')->default(1)->comment("(1: Available, 0: Sold Out)");
            $table->float('measurement');
            $table->float('price', 11, 2);
            $table->float('discounted_price', 11, 2)->default(0);
            $table->float('discount', 11, 2)->default(0);
            $table->string('discount_expires')->nullable()->comment('Expiration date for the discount');
            $table->float('stock', 11, 2)->default(0);
            $table->integer('stock_unit_id')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variants');
    }
}
