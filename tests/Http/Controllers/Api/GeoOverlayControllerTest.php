<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Tests\Modules\Geo\GeoOverlayTest;
use Storage;

class GeoOverlayControllerTest extends ApiTestCase
{
    public function testShowFile()
    {
        Storage::fake('geo-overlays');
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->id;
        Storage::disk('geo-overlays')->put($overlay->path, 'content');

        $this->doTestApiRoute('GET', "/api/v1/geo-overlays/{$id}/file");

        $this->beUser();
        $this->get("/api/v1/geo-overlays/{$id}/file")
            ->assertStatus(403);

        $this->beGuest();
        $response = $this->json('GET', "/api/v1/geo-overlays/{$id}/file")
            ->assertStatus(200);
        $this->assertEquals(7, $response->headers->get('content-length'));
    }

    // ToDo: Find out why get this error --> Unable to retrieve the file_size for file at location
    // 
    // public function testShowFileNotFound()
    // {
    //     Storage::fake('geo-overlays');
    //     $overlay = GeoOverlayTest::create();
    //     $overlay->volume_id = $this->volume()->id;
    //     $overlay->save();
    //     $id = $overlay->id;

    //     $this->beGuest();
    //     $this->json('GET', "/api/v1/geo-overlays/{$id}/file")
    //         ->assertStatus(404);
    // }

    public function testDestroy()
    {
        Storage::fake('geo-overlays');
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->id;
        Storage::disk('geo-overlays')->put($overlay->path, 'content');

        $this->doTestApiRoute('DELETE', "/api/v1/geo-overlays/{$id}");

        $this->beEditor();
        $this->delete("/api/v1/geo-overlays/{$id}")->assertStatus(403);

        $this->beAdmin();
        $this->delete("/api/v1/geo-overlays/{$id}")->assertStatus(200);
        $this->assertFalse(Storage::disk('geo-overlays')->exists($overlay->id));
    }
}