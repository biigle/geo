<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\MediaType;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Tests\Modules\Geo\GeoOverlayTest;
use Illuminate\Http\UploadedFile;
use Storage;
use Illuminate\Testing\Fluent\AssertableJson;

class VolumeGeoOverlayControllerTest extends ApiTestCase
{
    public function testIndex()
    {
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->volume_id;

        $this->beUser();
        $response = $this->get("/api/v1/volumes/{$id}/geo-overlays");
        $response->assertStatus(403);

        $this->beGuest();
        $response = $this->json('GET', "/api/v1/volumes/{$id}/geo-overlays")
            ->assertJsonFragment([$overlay->toArray()]);
        $response->assertStatus(200);
    }

    public function testUpdateGeotiff()
    {
        Storage::fake('geo-overlays');
        $id = $this->volume()->id;
    
        // Create overlay-instance
        $overlay = GeoOverlayTest::create();
        $overlay->save();
       
        $this->doTestApiRoute('PUT', "/api/v1/volumes/{$id}/geo-overlays/geotiff/{$overlay->id}");

        $this->beEditor();
        // 403: The client does not have access rights to the content
        $this->putJson("/api/v1/volumes/{$id}/geo-overlays/geotiff/{$overlay->id}", [
            'browsing_layer' => true,
            'context_layer' => false
        ])
        ->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors.
        // reason: no input data
        $this->json('PUT', "/api/v1/volumes/{$id}/geo-overlays/geotiff/{$overlay->id}")
        ->assertStatus(422);

        // now test if updating with data will succeed with the correct values being returned
        $response = $this->putJson("/api/v1/volumes/{$id}/geo-overlays/geotiff/{$overlay->id}", [
            'browsing_layer' => true,
            'context_layer' => false
        ]);
        $response
            ->assertStatus(200)            
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('browsing_layer', true)
                    ->where('context_layer', false)
            );
    }

    public function testStoreGeotiff() 
    {
        Storage::fake('geo-overlays');
        $id = $this->volume()->id;
    
        // Get current ID so we can predict the next ID later.
        $overlay = GeoOverlayTest::create();
        $overlayId = $overlay->id;
        $overlay->delete();
        $standard_gtiff = new UploadedFile(
            __DIR__."/../../../files/geotiff_standardEPSG2013.tif",
            'standardEPSG2013.tif',
            'image/tiff',
            null, 
            true
        );
        $user_defined_gtiff = new UploadedFile(
            __DIR__."/../../../files/geotiff_user_defined2011.tif",
            'user_defined2011.tif',
            'image/tiff',
            null, 
            true
        );

        $this->doTestApiRoute('POST', "/api/v1/volumes/{$id}/geo-overlays/geotiff");

        $this->beEditor();
        // 403: The client does not have access rights to the content
        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff", [
                'geotiff' => $standard_gtiff,
                'volumeId' => $id
            ])
            ->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors.
        $this->json('POST', "/api/v1/volumes/{$id}/geo-overlays/geotiff")
            ->assertStatus(422);

        $file = UploadedFile::fake()->create('overlay.tif');
        // check if "empty" geotiff fails properly
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff", ['geotiff' => $file])
            ->assertStatus(422);

        $this->assertFalse(GeoOverlay::exists());
        $this->assertFalse(Storage::disk('geo-overlays')->exists($overlay->path));

        // test upload of standard geoTIFF (in form of projected-Coordinate-Reference-System and EPSG-code)
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff", [
            'geotiff' => $standard_gtiff,
            'volumeId' => $id
        ])
        ->assertSuccessful();

        $overlay = GeoOverlay::where('volume_id', $id)->first();
        $this->assertNotNull($overlay);
        $this->assertEqualsWithDelta($overlay->top_left_lat, 57.097483857335796, 0.00001);
        $this->assertEqualsWithDelta($overlay->top_left_lng, -2.9198048485706547, 0.00001);
        $this->assertEqualsWithDelta($overlay->bottom_right_lat, 57.09845896597305, 0.00001);
        $this->assertEqualsWithDelta($overlay->bottom_right_lng, -2.917006253590798, 0.00001);
        $this->assertEquals($overlay->name, 'standardEPSG2013.tif');
        $this->assertEquals($overlay->browsing_layer, false);
        $this->assertEquals($overlay->context_layer, false);
        $response->assertJson($overlay->toArray(), $exact=false);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay->path));

        // testing upload of user-defined geoTIFF (GTModelType-Tag equals 32767)
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff", [
            'geotiff' => $user_defined_gtiff,
            'volumeId' => $id
        ])
        ->assertInvalid([
            'userDefined' => 'User-defined projected coordinate systems (PCS) are not supported. Provide a PCS using EPSG-system instead.',
        ]);
    }

    public function testStoreVideoVolume()
    {
        $id = $this->volume(['media_type_id' => MediaType::videoId()])->id;
        $file = UploadedFile::fake()->create('overlay.tif');
        $this->beAdmin();
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff", [
                'file' => $file,
            ])
            ->assertStatus(422);
    }
}