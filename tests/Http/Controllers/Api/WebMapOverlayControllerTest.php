<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Tests\Modules\Geo\GeoOverlayTest;

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

        // test invalid url
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => 'https://maps.gemar.de/geoserver/MSM96/wms',
            'volumeId' => $id
        ])->assertStatus(422);

        // test upload of valid WMS-URL (with query-parameters, but NO LAYERS declared)
        // should enter fallback method and return first valid layer anyways 
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => 'https://maps.geomar.de/geoserver/GEOMAR-Bathymetry/wms?service=WMS&version=1.1.0&request=GetMap&layers=',
            'volumeId' => $id
        ])->assertSuccessful();

        $overlay = GeoOverlay::where('volume_id', $id)->first();
        $this->assertNotNull($overlay);
        $this->assertEquals($overlay->browsing_layer, false);
        $this->assertEquals($overlay->context_layer, false);
        $this->assertEquals($overlay->attrs['layers'], ['AL632_Adlergrund_West_week1_50cm']);
        $this->assertEquals($overlay->name, 'AL632_Adlergrund_West_week1_50cm');
        $response->assertJson($overlay->toArray(), $exact=true);

        // test upload of valid WMS-URL (with query-parameters and SEVERAL LAYERS declared)
        $response2 = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => 'https://maps.geomar.de/geoserver/CONMAR/wms?service=WMS&version=1.1.0&request=GetMap&layers=CONMAR%3A_03a_bathymetry_poi,CONMAR:_01_tracks',
            'volumeId' => $id
        ])->assertSuccessful();
        $overlay2 = GeoOverlay::where('volume_id', $id)->where('id', $overlay->id+1)->first();
        $this->assertEquals($overlay2->attrs['layers'], ['CONMAR:_03a_bathymetry_poi', 'CONMAR:_01_tracks']);
        $this->assertEquals($overlay2->name, 'Bathymetry [POI]');
        $response2->assertJson($overlay2->toArray(), $exact=true);


        // test upload of valid WMS-URL (baseUrl without query-parameters)
        $response3 = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => 'https://maps.geomar.de/geoserver/MSM96/wms',
            'volumeId' => $id
        ])->assertSuccessful();
        $overlay3 = GeoOverlay::where('volume_id', $id)->where('id', $overlay2->id+1)->first();
        $this->assertEquals($overlay3->attrs['layers'], ['MSM96_EM122_IAP']);
        $this->assertEquals($overlay3->name, 'MSM96_EM122_IAP');
        $response3->assertJson($overlay3->toArray(), $exact=true);
    }
}