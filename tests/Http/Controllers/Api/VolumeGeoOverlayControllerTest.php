<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use File;
use Mockery;
use ApiTestCase;
use Illuminate\Http\UploadedFile;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Tests\Modules\Geo\GeoOverlayTest;

class VolumeGeoOverlayControllerTest extends ApiTestCase
{
    public function testIndex()
    {
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->volume_id;

        $this->doTestApiRoute('GET', "/api/v1/volumes/{$id}/geo-overlays");

        $this->beUser();
        $this->get("/api/v1/volumes/{$id}/geo-overlays");
        $this->assertResponseStatus(403);

        $this->beGuest();
        $this->json('GET', "/api/v1/volumes/{$id}/geo-overlays")
            ->seeJson([$overlay->toArray()]);
        $this->assertResponseOk();
    }

    public function testStorePlain()
    {
        $id = $this->volume()->id;

        $this->doTestApiRoute('POST', "/api/v1/volumes/{$id}/geo-overlays/plain");

        $this->beEditor();
        $this->post("/api/v1/volumes/{$id}/geo-overlays/plain");
        $this->assertResponseStatus(403);

        $this->beAdmin();
        $this->json('POST', "/api/v1/volumes/{$id}/geo-overlays/plain");
        $this->assertResponseStatus(422);

        File::shouldReceive('isDirectory')->once()->andReturn(true);

        $mock = Mockery::mock(UploadedFile::class);

        // For the validation rules
        $mock->shouldReceive('getPath')->andReturn('abc');
        $mock->shouldReceive('isValid')->andReturn(true);
        $mock->shouldReceive('getSize')->andReturn(2000);
        $mock->shouldReceive('getMimeType')->andReturn('image/jpeg');

        $mock->shouldReceive('move')->once()->with(config('geo.overlay_storage').'/'.$id, 1);
        $mock->shouldReceive('getClientOriginalName')->andReturn('map.jpg');

        $this->json('POST', "/api/v1/volumes/{$id}/geo-overlays/plain", [], [], ['file' => $mock]);
        $this->assertResponseStatus(422);

        $this->assertFalse(GeoOverlay::exists());

        $this->call('POST', "/api/v1/volumes/{$id}/geo-overlays/plain", [
            'top_left_lat' => 1.223344,
            'top_left_lng' => 1.334455,
            'bottom_right_lat' => 1.445566,
            'bottom_right_lng' => 1.667788,
        ], [], ['file' => $mock]);
        $this->assertResponseOk();

        $overlay = GeoOverlay::where('volume_id', $id)->first();
        $this->assertNotNull($overlay);
        $this->assertEquals($overlay->top_left_lat, 1.223344, '', 0.00001);
        $this->assertEquals($overlay->top_left_lng, 1.334455, '', 0.00001);
        $this->assertEquals($overlay->bottom_right_lat, 1.445566, '', 0.00001);
        $this->assertEquals($overlay->bottom_right_lng, 1.667788, '', 0.00001);
        $this->assertEquals($overlay->name, 'map.jpg');

        $this->seeJsonEquals($overlay->toArray());
    }
}
