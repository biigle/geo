<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\MediaType;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Tests\Modules\Geo\GeoOverlayTest;
use Illuminate\Http\UploadedFile;
use Storage;

class VolumeGeoOverlayControllerTest extends ApiTestCase
{
    public function testIndex()
    {
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->volume_id;

        $this->beUser();
        $response = $this->get("/api/v1/volumes/{$id}/geo-overlays");
        $response->assertStatus(403);

        $this->beGuest();
        $response = $this->json('GET', "/api/v1/volumes/{$id}/geo-overlays")
            ->assertJsonFragment([$overlay->toArray()]);
        $response->assertStatus(200);
    }

    public function testStoreGeotiff() 
    {
        Storage::fake('geo-overlays');
        $id = $this->volume()->id;
    
        // Get current ID so we can predict the next ID later.
        $overlay = GeoOverlayTest::create();
        $overlayId = $overlay->id;
        $overlay->delete();

        $this->doTestApiRoute('POST', "/api/v1/volumes/{$id}/geo-overlays/geotiff");

        $normal_file = new UploadedFile(
            __DIR__."/../../../files/geotiff_standardEPSG2013.tif",
            'geotiff_standardEPSG2013',
            'image/tiff',
            null, 
            true
        );

        $this->beEditor();
        // 403: The client does not have access rights to the content
        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            ['geotiff' => $normal_file,
            'volumeId' => $id]
            )->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors.
        $this->json('POST', "/api/v1/volumes/{$id}/geo-overlays/geotiff")
            ->assertStatus(422);

        $file = UploadedFile::fake()->create('overlay.tif');
        // check if "empty" geotiff fails properly
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff", ['geotiff' => $file])
            ->assertStatus(422);

        
    }
}