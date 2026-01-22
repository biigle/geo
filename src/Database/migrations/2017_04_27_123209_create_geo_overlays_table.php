<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeoOverlaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
         | A geo overlay is an image that can be placed on a world map. This can be a
         | bathymetric map of the area where a specific volume was recorded. Geo overlays
         | can be uploaded by volume admins and are displayed on the volume geo view.
         | Each overlay consists of an image and the geospatial information where the
         | image should be displayed on the world map. While the input format can vary
         | the geospatial information will always be converted to the latitude and
         | longitude of the top left and bottom right corners of the image in WGS 84
         | (EPSG:4326).
         */
        Schema::create('geo_overlays', function (Blueprint $table) {
            $table->increments('id');
            // A short description of the overlay.
            $table->string('name', 512);

            $table->float('top_left_lat');
            $table->float('top_left_lng');
            $table->float('bottom_right_lat');
            $table->float('bottom_right_lng');

            $table->integer('volume_id')->unsigned()->index();
            $table->foreign('volume_id')
                ->references('id')
                ->on('volumes')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('geo_overlays');
    }
}
