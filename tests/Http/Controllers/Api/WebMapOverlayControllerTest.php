<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use Mockery;
use ApiTestCase;
use Illuminate\Support\Str;
use Biigle\Modules\Geo\Services\Support\WebMapSource;

class WebMapOverlayControllerTest extends ApiTestCase
{
    public $mock = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->mock = Mockery::mock(WebMapSource::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->app->bind(WebMapSource::class, fn() => $this->mock);
    }
    public function testStoreWebMap()
    {
        $id = $this->volume()->id;

        $this->doTestApiRoute('POST', "/api/v1/volumes/{$id}/geo-overlays/webmap");

        $this->beEditor();
        // 403: The client does not have access rights to the content
        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/webmap",
            [
                'url' => 'https://maps.geomar.de/geoserver/GEOMAR-Bathymetry/wms',
                'volumeId' => $id
            ]
        )->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors (no input)
        $this->json('POST', "/api/v1/volumes/{$id}/geo-overlays/webmap")
            ->assertStatus(422);

        $xml = 'test 123';
        $this->mock->shouldReceive('request')->once()->andReturn($xml);

        // test upload of invalid WMS-URL (should cause invalidWMS error)
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => 'https://google.com',
            'volumeId' => $id
        ])->assertStatus(422);

        $xml = $this->getXMLResponse(1);
        $xml_array = $this->XmlToJson($xml);

        $this->mock->shouldReceive('request')->once()->andReturn($xml);

        // test upload of valid WMS-URL (with query-parameters, but NO LAYERS declared)
        // should enter fallback method and return first valid layer anyways 
        $url = 'https://maps.geomar.de/geoserver/GEOMAR-Bathymetry/wms?service=WMS&version=1.1.0&request=GetMap&layers=';
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => $url,
            'volumeId' => $id
        ])->assertSuccessful();

        $overlay = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay);
        $this->assertTrue($overlay['browsing_layer']);
        $this->assertEquals(Str::before($url, '?'), $overlay['attrs']['url']);
        $this->assertEquals([$xml_array['Layer']['Layer']['Name']], $overlay['attrs']['layers']);
        $this->assertEquals($xml_array['Layer']['Layer']['Title'], $overlay['name']);
    }

    public function testStoreWebMapLayers()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        $xml = $this->getXMLResponse(3);
        $xml_array = $this->XmlToJson($xml);
        $xml_names = array_map(fn($l) => $l['Name'], $xml_array['Layer']['Layer']);
        $this->mock->shouldReceive('request')->once()->andReturn($xml);

        // test upload of valid WMS-URL (with query-parameters and SEVERAL LAYERS declared)
        $url = 'https://maps.geomar.de/geoserver/CONMAR/wms?service=WMS&version=1.1.0&request=GetMap&layers=Name_0,Name_1,Name_2';
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => $url,
            'volumeId' => $id
        ])->assertSuccessful();

        $overlay = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay);
        $this->assertTrue($overlay['browsing_layer']);
        $this->assertEquals(Str::before($url, '?'), $overlay['attrs']['url']);
        $this->assertEquals($xml_names, $overlay['attrs']['layers']);
        $this->assertEquals($xml_array['Layer']['Layer'][0]['Title'], $overlay['name']);
    }

    public function testStoreWebMapBaseURL()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        $xml = $this->getXMLResponse(3, true);
        $xml_array = $this->XmlToJson($xml)['Layer']['Layer'];
        $this->mock->shouldReceive('request')->once()->andReturn($xml);

        // test upload of valid WMS-URL (baseUrl without query-parameters)
        $url = 'https://maps.geomar.de/geoserver/MSM96/wms';
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => $url,
            'volumeId' => $id
        ])->assertSuccessful();

        $overlay = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay);
        $this->assertTrue($overlay['browsing_layer']);
        $this->assertEquals($url, $overlay['attrs']['url']);
        $this->assertEquals([$xml_array[1]['Name']], $overlay['attrs']['layers']);
        $this->assertEquals($xml_array[1]['Title'], $overlay['name']);
    }

    public function getXMLResponse($layers, $hasInvalidLayer = false)
    {
        $content = '<?xml version="1.0" encoding="UTF-8" ?><Capability><Layer>';
        for ($i = 0; $i < $layers; $i++) {
            if ($hasInvalidLayer) {
                // First layer is invalid due to a missing name tag
                $content .= "<Layer><Title>Title_$i</Title></Layer>";
                $hasInvalidLayer = false;
            } else {
                $content .= "<Layer><Name>Name_$i</Name><Title>Title_$i</Title></Layer>";
            }
        }
        $content .= "</Layer></Capability>";
        return $content;
    }

    public function XmlToJson($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml)), true);
    }
}