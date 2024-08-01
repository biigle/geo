<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
         | A web map overlay consists of a url that leads to a HTTP interface for requesting 
         | georeferenced map images from a server, which can be placed on a world map. 
         | This can be a bathymetric map of the area where a specific volume was recorded. 
         | Web map overlays can be uploaded by volume admins and are displayed on the volumes 
         | geoMapModal.
         */

        Schema::create('web_map_overlays', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('url', 512)->unique();
            $table->json('layers');
            $table->boolean('browsing_layer')->default(0);
            $table->boolean('context_layer')->default(0);
            $table->integer('volume_id')->unsigned()->index();
            $table->foreign('volume_id')
                  ->references('id')
                  ->on('volumes')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_map_overlays');
    }
};
