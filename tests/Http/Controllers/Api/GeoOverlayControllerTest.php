<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Tests\Modules\Geo\GeoOverlayTest;
use Storage;

class GeoOverlayControllerTest extends ApiTestCase
{

    public function testIndex()
    {
        Storage::fake('geo-overlays');
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        // modify overlay context_layer value
        $overlay->context_layer = true;
        $overlay->save();

        $overlay2 = GeoOverlayTest::create();
        $overlay2->volume_id = $this->volume()->id;
        // modify overlay2 browsing_layer value
        $overlay2->browsing_layer = true;
        $overlay2->save();

        $id = $overlay->volume_id;

        $this->beUser();
        $response = $this->get("/api/v1/volumes/{$id}/geo-overlays");
        $response->assertStatus(403);

        $this->beGuest();
        // check if base-case (without layer_type variable) works
        $response = $this->json('GET', "/api/v1/volumes/{$id}/geo-overlays")
            ->assertJsonFragment([$overlay->toArray()], [$overlay2->toArray()]);
        $response->assertStatus(200);

        // check if layer_type=browsing_layer works as expected (return only second overlay)
        $this->get("/api/v1/volumes/{$id}/geo-overlays/?layer_type=browsing_layer")
            ->assertJsonFragment([$overlay2->toArray()])
            ->assertJsonMissing([$overlay->toArray()])
            ->assertStatus(200);
        
        // check if layer_type=context_layer works as expected (return only first overlay)
        $this->get("/api/v1/volumes/{$id}/geo-overlays/?layer_type=context_layer")
        ->assertJsonFragment([$overlay->toArray()])
        ->assertJsonMissing([$overlay2->toArray()])
        ->assertStatus(200);
    }
    
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
    
    public function testShowFileNotFound()
    {
        Storage::fake('geo-overlays');
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->id;

        $this->beGuest();
        $this->json('GET', "/api/v1/geo-overlays/{$id}/file")
            ->assertStatus(404);
    }

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