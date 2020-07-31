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

        $this->doTestApiRoute('GET', "/api/v1/volumes/{$id}/geo-overlays");

        $this->beUser();
        $response = $this->get("/api/v1/volumes/{$id}/geo-overlays");
        $response->assertStatus(403);

        $this->beGuest();
        $response = $this->json('GET', "/api/v1/volumes/{$id}/geo-overlays")
            ->assertJsonFragment([$overlay->toArray()]);
        $response->assertStatus(200);
    }

    public function testIndexVideoVolume()
    {
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume([
            'media_type_id' => MediaType::videoId(),
        ])->id;
        $overlay->save();
        $id = $overlay->volume_id;

        $this->beGuest();
        $this->json('GET', "/api/v1/volumes/{$id}/geo-overlays")->assertStatus(404);
    }

    public function testStorePlain()
    {
        Storage::fake('geo-overlays');
        $id = $this->volume()->id;
        // Get current ID so we can predict the next ID later.
        $overlay = GeoOverlayTest::create();
        $overlayId = $overlay->id;
        $overlay->delete();

        $this->doTestApiRoute('POST', "/api/v1/volumes/{$id}/geo-overlays/plain");

        $this->beEditor();
        $this->post("/api/v1/volumes/{$id}/geo-overlays/plain")->assertStatus(403);

        $this->beAdmin();
        $this->json('POST', "/api/v1/volumes/{$id}/geo-overlays/plain")
            ->assertStatus(422);

        $file = UploadedFile::fake()->create('overlay.png');
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/plain", ['file' => $file])
            ->assertStatus(422);

        $this->assertFalse(GeoOverlay::exists());
        $this->assertFalse(Storage::disk('geo-overlays')->exists($overlay->path));

        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/plain", [
                'top_left_lat' => 1.223344,
                'top_left_lng' => 1.334455,
                'bottom_right_lat' => 1.445566,
                'bottom_right_lng' => 1.667788,
                'file' => $file,
            ])
            ->assertSuccessful();

        $overlay = GeoOverlay::where('volume_id', $id)->first();
        $this->assertNotNull($overlay);
        $this->assertEqualsWithDelta($overlay->top_left_lat, 1.223344, 0.00001);
        $this->assertEqualsWithDelta($overlay->top_left_lng, 1.334455, 0.00001);
        $this->assertEqualsWithDelta($overlay->bottom_right_lat, 1.445566, 0.00001);
        $this->assertEqualsWithDelta($overlay->bottom_right_lng, 1.667788, 0.00001);
        $this->assertEquals($overlay->name, 'overlay.png');
        $response->assertExactJson($overlay->toArray());
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay->path));
    }

    public function testStoreVideoVolume()
    {
        $id = $this->volume(['media_type_id' => MediaType::videoId()])->id;
        $file = UploadedFile::fake()->create('overlay.png');
        $this->beAdmin();
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/plain", [
                'top_left_lat' => 1.223344,
                'top_left_lng' => 1.334455,
                'bottom_right_lat' => 1.445566,
                'bottom_right_lng' => 1.667788,
                'file' => $file,
            ])
            ->assertStatus(422);
    }
}
