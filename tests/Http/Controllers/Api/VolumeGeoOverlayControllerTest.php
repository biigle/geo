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

        $this->beEditor();
        $this->post("/api/v1/volumes/{$id}/geo-overlays/geotiff")->assertStatus(403);

        $this->beAdmin();
        $this->json('POST', "/api/v1/volumes/{$id}/geo-overlays/geotiff")
            ->assertStatus(422);

        // $file = UploadedFile::fake()->create('overlay.png');
        $user_defined_file = new UploadedFile(__DIR__."/../../../files/geotiff_user_defined2011.tif");
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff", ['file' => $user_defined_file])
            ->assertStatus(422);
    }
}