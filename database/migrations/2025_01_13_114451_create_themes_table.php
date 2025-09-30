<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('primary_color');
            $table->string('secondary_color');
            $table->string('tertiary_color')->nullable();
            $table->string('button_color');
            $table->string('button_text_color');
            $table->string('active_navigation_color');
            $table->string('inactive_navigation_color');
            $table->string('add_cart_button_color');
            $table->string('add_cart_text_color');
            $table->string('add_cart_border_color');
            $table->string('primary_background_color')->nullable();
            $table->string('secondary_background_color')->nullable();
            $table->string('third_background_color')->nullable();
            $table->string('fourth_background_color')->nullable();
            $table->string('fifth_background_color')->nullable();
            $table->string('link_color')->nullable();
            $table->string('color_6')->nullable();
            $table->string('color_7')->nullable();
            $table->boolean('status')->default(1);
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
        Schema::dropIfExists('themes');
    }
}
