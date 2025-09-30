<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeColorAndColorCodeNullableInProductVariantsTable extends Migration
{
  public function up()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('color_code')->nullable()->change();
            $table->string('color')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('color_code')->nullable(false)->change();
            $table->string('color')->nullable(false)->change();
        });
    }
}
