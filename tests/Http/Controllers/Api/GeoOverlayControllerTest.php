<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use Storage;
use ApiTestCase;
use Biigle\Modules\Geo\GeoOverlay;

class GeoOverlayControllerTest extends ApiTestCase
{

    public function testUpdateGeoOverlay()
    {
        Storage::fake('geo-overlays');
        $id = $this->volume()->id;

        // Create overlay-instance
        $overlay = GeoOverlay::factory()->create();
        $overlay->save();

        $this->doTestApiRoute('PUT', "/api/v1/volumes/{$id}/geo-overlays/{$overlay->id}");

        $this->beEditor();
        // 403: The client does not have access rights to the content
        $this->putJson("/api/v1/volumes/{$id}/geo-overlays/{$overlay->id}", [
            'browsing_layer' => true,
            'use_layer' => true
        ])
            ->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors.
        // reason: no input data
        $this->json('PUT', "/api/v1/volumes/{$id}/geo-overlays/{$overlay->id}")
            ->assertStatus(422);

        $this->putJson("/api/v1/volumes/{$id}/geo-overlays/{$overlay->id}", [
            'browsing_layer' => 'test',
        ])->assertStatus(422);

        $this->assertFalse($overlay->browsing_layer);
        // now test if updating with data will succeed with the correct values being returned
        $response = $this->putJson("/api/v1/volumes/{$id}/geo-overlays/{$overlay->id}", [
            'browsing_layer' => true,
        ])->assertStatus(200);

        $this->assertTrue(json_decode($response->getContent())->browsing_layer);

        $this->putJson("/api/v1/volumes/{$id}/geo-overlays/{$overlay->id}", [
            'layer_index' => -1
        ])->assertStatus(422);

        $this->putJson("/api/v1/volumes/{$id}/geo-overlays/{$overlay->id}", [
            'layer_index' => 1.1
        ])->assertStatus(422);

        $this->putJson("/api/v1/volumes/{$id}/geo-overlays/{$overlay->id}", [
            'layer_index' => GeoOverlay::count()
        ])->assertStatus(422);

        $this->putJson("/api/v1/volumes/{$id}/geo-overlays/{$overlay->id}", [
            'layer_index' => 0
        ])->assertStatus(200);
    }

    public function testGetOverlays()
    {
        $urlTemplate = "http://localhost:8000/storage/geo-overlays/:id/:id_tiles//{TileGroup}/{z}-{x}-{y}.png";
        // This overlay is still processing and should not be returned
        GeoOverlay::factory()->create(['volume_id' => $this->volume()->id]);
        // This overlay is already processed
        $overlay = GeoOverlay::factory()->create([
            'volume_id' => $this->volume()->id,
            'processed' => true
        ]);
        $overlayCount = GeoOverlay::where('volume_id', $this->volume()->id)->count();
        $id = $this->volume()->id;

        $this->beAdmin();
        $res = $this->getJson("/api/v1/volumes/{$id}/geo-overlays");
        $res->assertSuccessful();
        $overlay = json_decode($res->getContent(), true);

        $this->assertEquals(2, $overlayCount);
        $this->assertCount(1, $overlay['geoOverlays']);
        $this->assertEquals($urlTemplate, $overlay["urlTemplate"]);
    }

    public function testDestroy()
    {
        Storage::fake('geo-overlays');
        $overlay = GeoOverlay::factory()->create();
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