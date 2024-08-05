<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\Geo\WebMapOverlay;
use Biigle\Tests\Modules\Geo\WebMapOverlayTest;

class WebMapOverlayControllerTest extends ApiTestCase
{

    public function testStoreWebMap() 
    {
        $id = $this->volume()->id;

        $this->doTestApiRoute('POST', "/api/v1/volumes/{$id}/geo-overlays/webmap");

        $this->beEditor();
        // 403: The client does not have access rights to the content
        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/webmap", [
                'url' => 'https://maps.geomar.de/geoserver/GEOMAR-Bathymetry/wms',
                'volumeId' => $id
            ])
            ->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors (no input)
        $this->json('POST', "/api/v1/volumes/{$id}/geo-overlays/webmap")
            ->assertStatus(422);

        // test upload of invalid WMS-URL (should cause invalidWMS error)
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => 'https://google.com',
            'volumeId' => $id
        ])->assertStatus(422);

        // test upload of valid WMS-URL (with query-parameters, but NO LAYERS declared)
        // should enter fallback method and return first valid layer anyways 
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => 'https://maps.geomar.de/geoserver/GEOMAR-Bathymetry/wms?service=WMS&version=1.1.0&request=GetMap&layers=',
            'volumeId' => $id
        ])->assertSuccessful();

        $overlay = WebMapOverlay::where('volume_id', $id)->first();
        $this->assertNotNull($overlay);
        $this->assertEquals($overlay->browsing_layer, false);
        $this->assertEquals($overlay->context_layer, false);
        $this->assertEquals($overlay->layers, ['CV_Accepted_GEBCO23_15s_WGS84']);
        $this->assertEquals($overlay->name, 'Cabo Verde Bathymetry Compilation');
        $response->assertJson($overlay->toArray(), $exact=true);

        // test upload of valid WMS-URL (with query-parameters and SEVERAL LAYERS declared)
        $response2 = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => 'https://maps.geomar.de/geoserver/CONMAR/wms?service=WMS&version=1.1.0&request=GetMap&layers=CONMAR%3A_03a_bathymetry_poi,CONMAR:_01_tracks',
            'volumeId' => $id
        ])->assertSuccessful();
        $overlay2 = WebMapOverlay::where('volume_id', $id)->where('id', $overlay->id+1)->first();
        $this->assertEquals($overlay2->layers, ['CONMAR:_03a_bathymetry_poi', 'CONMAR:_01_tracks']);
        $this->assertEquals($overlay2->name, 'Bathymetry [POI]');
        $response2->assertJson($overlay2->toArray(), $exact=true);


        // test upload of valid WMS-URL (baseUrl without query-parameters)
        $response3 = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => 'https://maps.geomar.de/geoserver/MSM96/wms',
            'volumeId' => $id
        ])->assertSuccessful();
        $overlay3 = WebMapOverlay::where('volume_id', $id)->where('id', $overlay2->id+1)->first();
        $this->assertEquals($overlay3->layers, ['MSM96_EM122_IAP']);
        $this->assertEquals($overlay3->name, 'MSM96_EM122_IAP');
        $response3->assertJson($overlay3->toArray(), $exact=true);
    }

    public function testUpdateWebMap()
    {
        $id = $this->volume()->id;
    
        // Create overlay-instance
        $overlay = WebMapOverlayTest::create();
        $overlay->save();
       
        $this->doTestApiRoute('PUT', "/api/v1/volumes/{$id}/geo-overlays/webmap/{$overlay->id}");

        $this->beEditor();
        // 403: The client does not have access rights to the content
        $this->putJson("/api/v1/volumes/{$id}/geo-overlays/webmap/{$overlay->id}", [
            'layer_type' => 'browsingLayer',
            'value' => false
        ])
        ->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors.
        // reason: no input data
        $this->json('PUT', "/api/v1/volumes/{$id}/geo-overlays/webmap/{$overlay->id}")
        ->assertStatus(422);

        // now test if updating with data will succeed with the correct values being returned
        $response = $this->putJson("/api/v1/volumes/{$id}/geo-overlays/webmap/{$overlay->id}", [
            'layer_type' => 'browsingLayer',
            'value' => true
        ]);
        $response
            ->assertStatus(200)            
            ->assertJson([
                'browsing_layer' => true,
                'context_layer' => false
            ]);
    }

    public function testDestroyWebMap()
    {
        $overlay = WebMapOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->id;

        $this->doTestApiRoute('DELETE', "/api/v1/web-map-overlays/{$id}");

        $this->beEditor();
        $this->delete("/api/v1/web-map-overlays/{$id}")->assertStatus(403);

        $this->beAdmin();
        $this->delete("/api/v1/web-map-overlays/{$id}")->assertStatus(200);
    }
}