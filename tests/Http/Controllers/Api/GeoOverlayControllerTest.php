<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use Storage;
use ApiTestCase;
use Biigle\Volume;
use Biigle\Modules\Geo\GeoOverlay;

class GeoOverlayControllerTest extends ApiTestCase
{

    public function testUpdateGeoOverlay()
    {
        $id = $this->volume()->id;

        // Create overlay-instance
        $overlay = GeoOverlay::factory()->create(['volume_id' => $id]);
        $updated_overlays = [
            [
                'id' => $overlay->id,
                'volume_id' => $overlay->volume_id,
                'name' => $overlay->name,
                'browsing_layer' => false
            ]
        ];

        $this->doTestApiRoute('PUT', "/api/v1/volumes/{$id}/geo-overlays");

        $this->beEditor();
        // 403: The client does not have access rights to the content
        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors.
        // reason: no input data
        $this->json('PUT', "/api/v1/volumes/{$id}/geo-overlays")
            ->assertInvalid(['updated_overlays']);

        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => []
        ])->assertInvalid(['updated_overlays']);

        // invalid type
        $updated_overlays[0]['browsing_layer'] = "test";
        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertInvalid(['updated_overlays.0.browsing_layer']);

        // invalid id
        $updated_overlays[0]['id'] = 99;
        $updated_overlays[0]['browsing_layer'] = true;
        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertInvalid(['invalidIds']);

        $updated_overlays[0]['id'] = $overlay->id;
        // now test if updating with data will succeed with the correct values being returned
        $response = $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertStatus(200);

        $this->assertTrue($overlay->refresh()->browsing_layer);
    }

    public function testUpdateGeoOverlayWrongOverlayIds()
    {
        $id = $this->volume()->id;
        $vol = Volume::factory()->create();

        // Overlay belongs to other volume
        $overlay = GeoOverlay::factory()->create(['volume_id' => $vol->id]);
        $updated_overlays = [
            [
                'id' => $overlay->id,
                'volume_id' => $overlay->volume_id,
                'name' => $overlay->name,
                'layer_index' => 0
            ]
        ];
        $this->beAdmin();

        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertInvalid(['invalidIds']);

        $overlay = GeoOverlay::factory()->create(['volume_id' => $id]);
        $updated_overlays = [
            [
                'id' => $overlay->id,
                'volume_id' => $overlay->volume_id,
                'name' => $overlay->name,
                'layer_index' => 0
            ]
        ];

        $this->putJson("/api/v1/volumes/{$vol->id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertForbidden();
    }

    public function testUpdateGeoOverlayLayerIndex()
    {
        $id = $this->volume()->id;
        $overlay = GeoOverlay::factory()->create(['volume_id' => $id]);
        $updated_overlays = [
            [
                'id' => $overlay->id,
                'volume_id' => $overlay->volume_id,
                'name' => $overlay->name,
                'layer_index' => 0
            ]
        ];
        $this->beAdmin();

        $updated_overlays[0]['layer_index'] = -1;
        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertInvalid(['updated_overlays.0.layer_index']);

        $updated_overlays[0]['layer_index'] = 0.1;
        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertInvalid(['updated_overlays.0.layer_index']);

        $updated_overlays[0]['layer_index'] = GeoOverlay::where('volume_id', $id)->count() + 1;
        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertInvalid(['updated_overlays.0.layer_index']);

        $updated_overlays[0]['layer_index'] = 0;
        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertStatus(200);
    }

    public function testUpdateGeoOverlayInvalidRequest()
    {
        Storage::fake('geo-overlays');
        $id = $this->volume()->id;
        $overlay = GeoOverlay::factory()->create(['volume_id' => $id]);
        $updated_overlays = [
            [
                'id' => $overlay->id,
                'volume_id' => $overlay->volume_id,
                'name' => $overlay->name
            ]
        ];
        $this->beAdmin();

        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertInvalid(['invalidUpdateKey']);

        $updated_overlays[0]['layer_index'] = 0;
        $updated_overlays[0]['browsing_layer'] = true;

        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertInvalid(['invalidUpdateKey']);

        $overlay2 = GeoOverlay::factory()->create(['volume_id' => $id]);
        $updated_overlays = [
            [
                'id' => $overlay->id,
                'volume_id' => $overlay->volume_id,
                'name' => $overlay->name,
                'browsing_layer' => true
            ],
            [
                'id' => $overlay2->id,
                'volume_id' => $overlay2->volume_id,
                'name' => $overlay2->name,
                'layer_index' => 1
            ]
        ];

        $this->putJson("/api/v1/volumes/{$id}/geo-overlays", [
            'updated_overlays' => $updated_overlays
        ])->assertInvalid(['invalidRequest']);
    }

    public function testGetOverlays()
    {
        $urlTemplate = "http://localhost:8000/storage/geo-overlays/:id/:id_tiles/{TileGroup}/{z}-{x}-{y}.png";
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
