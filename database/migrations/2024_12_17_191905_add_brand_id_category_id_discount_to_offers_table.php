<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBrandIdCategoryIdDiscountToOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->unsignedBigInteger('section_id')->nullable()->after('position');
            $table->unsignedBigInteger('brand_id')->nullable()->after('section_id');
            $table->unsignedBigInteger('category_id')->nullable()->after('brand_id');
            $table->integer('discount')->default(0)->after('category_id');




            $table->foreign('section_id')->references('id')->on('sections');
            $table->foreign('brand_id')->references('id')->on('brands');
            $table->foreign('category_id')->references('id')->on('categories');
        });

        // Optionally, drop 'section_position' if it's no longer needed
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('section_position');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('offers', function (Blueprint $table) {
            // Re-add 'section_position' if it was dropped
            $table->string('section_position')->after('position');

            $table->dropColumn(['section_id', 'brand_id', 'category_id', 'discount']);

            // Drop foreign keys if they were added
            /*
            $table->dropForeign(['section_id']);
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['category_id']);
            */
        });
    }
}
