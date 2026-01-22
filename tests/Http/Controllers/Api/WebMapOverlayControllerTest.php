<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use Exception;
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
        ])->assertStatus(422);

        $xml = $this->getXMLResponse(1);
        $xml_array = $this->XmlToJson($xml);

        $this->mock->shouldReceive('request')->once()->andReturn($xml);

        // test upload of valid WMS-URL (with query-parameters, but NO LAYERS declared)
        // should enter fallback method and return first valid layer anyways 
        $url = 'https://maps.geomar.de/geoserver/GEOMAR-Bathymetry/wms?service=WMS&version=1.1.0&request=GetMap&layers=';
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => $url,
        ])->assertSuccessful();

        $overlay = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay);
        $this->assertTrue($overlay['browsing_layer']);
        $this->assertEquals(Str::before($url, '?'), $overlay['attrs']['url']);
        $this->assertEquals($xml_array['Layer']['Layer']['Name'], $overlay['attrs']['layer']);
        $this->assertEquals($xml_array['Layer']['Layer']['Title'], $overlay['name']);
    }

    public function testStoreWebMapLayer()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        $xml = $this->getXMLResponse(3);
        $xml_array = $this->XmlToJson($xml);
        $xml_names = array_map(fn($l) => $l['Name'], $xml_array['Layer']['Layer']);
        $this->mock->shouldReceive('request')->once()->andReturn($xml);

        // test upload of valid WMS-URL (with query-parameters and SEVERAL LAYERS declared)
        $url = 'https://maps.geomar.de/geoserver/CONMAR/wms?service=WMS&version=1.1.0&request=GetMap&layers=Name_0';
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => $url,
        ])->assertSuccessful();

        $overlay = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay);
        $this->assertTrue($overlay['browsing_layer']);
        $this->assertEquals(Str::before($url, '?'), $overlay['attrs']['url']);
        $this->assertEquals($xml_names[0], $overlay['attrs']['layer']);
        $this->assertEquals($xml_array['Layer']['Layer'][0]['Title'], $overlay['name']);
    }

    public function testStoreTooManyLayers()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        $url = 'https://maps.geomar.de/geoserver/CONMAR/wms?service=WMS&version=1.1.0&request=GetMap&layers=Name_0,Name_1';
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => $url,
        ])->assertInvalid(['tooManyLayers']);

        $url = 'https://maps.geomar.de/geoserver/CONMAR/wms?service=WMS&version=1.1.0&request=GetMap&layers=Name_0 Name_1';
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => $url,
        ])->assertInvalid(['tooManyLayers']);
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
        ])->assertSuccessful();

        $overlay = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay);
        $this->assertTrue($overlay['browsing_layer']);
        $this->assertEquals($url, $overlay['attrs']['url']);
        $this->assertEquals($xml_array[1]['Name'], $overlay['attrs']['layer']);
        $this->assertEquals($xml_array[1]['Title'], $overlay['name']);
    }

    public function testStoreNoValidLayer()
    {
        $id = $this->volume()->id;
        $xml = $this->getXMLResponse(1, true);
        $this->beAdmin();

        $this->mock->shouldReceive('request')->once()->andReturn($xml);

        $url = 'https://maps.geomar.de/geoserver/GEOMAR-Bathymetry/wms?service=WMS&version=1.1.0&request=GetMap&layers=';
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => $url,
        ])->assertInvalid(['noValidLayer']);
    }

    public function testStoreWebMapServiceWithCoordsEPSG4326()
    {

        $this->beAdmin();
        $id = $this->volume()->id;
        // CRS with EPSG:4326 does not need transformation
        $xml = '<?xml version="1.0" encoding="UTF-8" ?><Capability><Layer><Layer>' .
            '<Name>Name_0</Name><Title>Title_0</Title>' .
            '<BoundingBox SRS="EPSG:3785" minx="618304.4021614345" miny="5922088.138387541" maxx="1749459.3099797484" maxy="7349826.105804681"/>
        <BoundingBox SRS="EPSG:4326" minx="5.554322947" miny="47.069383893" maxx="15.715660370999998" maxy="55.118670158"/>' .
            '</Layer></Layer></Capability>';

        $xml_array = $this->XmlToJson($xml)['Layer']['Layer'];
        $this->mock->shouldReceive('request')->once()->andReturn($xml);

        $url = 'https://maps.geomar.de/geoserver/MSM96/wms';
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => $url,
        ])->assertSuccessful();

        $round = fn($c) => round($c, 13);
        $coords = $xml_array['BoundingBox'][1]['@attributes'];
        $overlay = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay);
        $this->assertTrue($overlay['browsing_layer']);
        $this->assertEquals($url, $overlay['attrs']['url']);
        $this->assertEquals($xml_array['Name'], $overlay['attrs']['layer']);
        $this->assertEquals($xml_array['Title'], $overlay['name']);
        $this->assertEquals('EPSG:4326', $coords['SRS']);
        $this->assertEquals($round($coords['minx']), $overlay['attrs']['top_left_lng']);
        $this->assertEquals($round($coords['miny']), $overlay['attrs']['top_left_lat']);
        $this->assertEquals($round($coords['maxx']), $overlay['attrs']['bottom_right_lng']);
        $this->assertEquals($round($coords['maxy']), $overlay['attrs']['bottom_right_lat']);
    }

    public function testStoreWebMapServiceWithCoordsEPSG32647()
    {

        $this->beAdmin();
        $id = $this->volume()->id;
        // CRS with EPSG:32647 needs transformation
        $xml = '<?xml version="1.0" encoding="UTF-8" ?><Capability><Layer><Layer>' .
            '<Name>Name_0</Name><Title>Title_0</Title>' .
            '<BoundingBox SRS="EPSG:32647" minx="166021.44" miny="1116915.04" maxx="500000.0" maxy="5572242.78"/>' .
            '</Layer></Layer></Capability>';

        $xml_array = $this->XmlToJson($xml)['Layer']['Layer'];
        $this->mock->shouldReceive('request')->once()->andReturn($xml);

        $url = 'https://maps.geomar.de/geoserver/MSM96/wms';
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => $url,
        ])->assertSuccessful();

        $overlay = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay);
        $this->assertTrue($overlay['browsing_layer']);
        $this->assertEquals($url, $overlay['attrs']['url']);
        $this->assertEquals($xml_array['Name'], $overlay['attrs']['layer']);
        $this->assertEquals($xml_array['Title'], $overlay['name']);
        $this->assertEquals(95.9531411952078, $overlay['attrs']['top_left_lng']);
        $this->assertEquals(10.0899544815049, $overlay['attrs']['top_left_lat']);
        $this->assertEquals(99, $overlay['attrs']['bottom_right_lng']);
        $this->assertEquals(50.3023009095724, $overlay['attrs']['bottom_right_lat']);
    }

    public function storeNotExistingLayer()
    {
        $id = $this->volume()->id;
        $xml = $this->getXMLResponse(1);
        $this->beAdmin();

        $this->mock->shouldReceive('request')->once()->andReturn($xml);

        $url = 'https://maps.geomar.de/geoserver/GEOMAR-Bathymetry/wms?service=WMS&version=1.1.0&request=GetMap&layers=test123';
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/webmap", [
            'url' => $url,
        ])->assertInvalid(['noValidLayer']);
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
