<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeoOverlaysTable extends Migration
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
            $table->integer('volume_id')->unsigned()->index();
            $table->foreign('volume_id')
                  ->references('id')
                  ->on('volumes')
                  ->onDelete('cascade');
            $table->string('type', 10)->nullable();
            $table->boolean('browsing_layer')->default(0);
            $table->boolean('context_layer')->default(0);
            // the position of the overlay (can be set by the user) in case of mutliple uploaded overlays in the same volume 
            $table->integer('layer_index')->nullable();
            // follwoing attributes are possible in attrs-column:
            // if(type == 'geotiff'): 'top_left_lat', 'top_left_lng', 'bottom_right_lat', 'bottom_right_lng', 'width', 'height' 
            // if(type == 'webmap'): 'url', 'layers'
            $table->json('attrs')->default('{}');
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
