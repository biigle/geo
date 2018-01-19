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
        $response = $this->get("/api/v1/volumes/{$id}/geo-overlays");
        $response->assertStatus(403);

        $this->beGuest();
        $response = $this->json('GET', "/api/v1/volumes/{$id}/geo-overlays")
            ->assertJsonFragment([$overlay->toArray()]);
        $response->assertStatus(200);
    }

    public function testStorePlain()
    {
        $id = $this->volume()->id;
        // Get current ID so we can predict the next ID later.
        $overlay = GeoOverlayTest::create();
        $overlayId = $overlay->id;
        $overlay->delete();

        $this->doTestApiRoute('POST', "/api/v1/volumes/{$id}/geo-overlays/plain");

        $this->beEditor();
        $response = $this->post("/api/v1/volumes/{$id}/geo-overlays/plain");
        $response->assertStatus(403);

        $this->beAdmin();
        $response = $this->json('POST', "/api/v1/volumes/{$id}/geo-overlays/plain");
        $response->assertStatus(422);

        File::shouldReceive('isDirectory')->once()->andReturn(true);

        $mock = Mockery::mock(UploadedFile::class);

        // For the validation rules
        $mock->shouldReceive('getPath')->andReturn('abc');
        $mock->shouldReceive('isValid')->andReturn(true);
        $mock->shouldReceive('getSize')->andReturn(2000);
        $mock->shouldReceive('getMimeType')->andReturn('image/jpeg');

        $mock->shouldReceive('move')->once()->with(config('geo.overlay_storage').'/'.$id, $overlayId + 1);
        $mock->shouldReceive('getClientOriginalName')->andReturn('map.jpg');
        $mock->shouldReceive('getClientOriginalExtension')->andReturn('map.jpg');

        $response = $this->json('POST', "/api/v1/volumes/{$id}/geo-overlays/plain", [], [], ['file' => $mock]);
        $response->assertStatus(422);

        $this->assertFalse(GeoOverlay::exists());

        $response = $this->call('POST', "/api/v1/volumes/{$id}/geo-overlays/plain", [
            'top_left_lat' => 1.223344,
            'top_left_lng' => 1.334455,
            'bottom_right_lat' => 1.445566,
            'bottom_right_lng' => 1.667788,
        ], [], ['file' => $mock]);
        $response->assertStatus(200);

        $overlay = GeoOverlay::where('volume_id', $id)->first();
        $this->assertNotNull($overlay);
        $this->assertEquals($overlay->top_left_lat, 1.223344, '', 0.00001);
        $this->assertEquals($overlay->top_left_lng, 1.334455, '', 0.00001);
        $this->assertEquals($overlay->bottom_right_lat, 1.445566, '', 0.00001);
        $this->assertEquals($overlay->bottom_right_lng, 1.667788, '', 0.00001);
        $this->assertEquals($overlay->name, 'map.jpg');

        $response->assertExactJson($overlay->toArray());
    }
}
