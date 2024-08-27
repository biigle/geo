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
            'layer_type' => 'browsingLayer',
            'value' => true
        ])
        ->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors.
        // reason: no input data
        $this->json('PUT', "/api/v1/volumes/{$id}/geo-overlays/geotiff/{$overlay->id}")
        ->assertStatus(422);

        // now test if updating with data will succeed with the correct values being returned
        $response = $this->putJson("/api/v1/volumes/{$id}/geo-overlays/geotiff/{$overlay->id}", [
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

    public function testStoreGeotiff() 
    {
        Storage::fake('geo-overlays');
        $id = $this->volume()->id;
    
        // Get current ID so we can predict the next ID later.
        $overlay = GeoOverlayTest::create();
        $overlayId = $overlay->id;
        $overlay->delete();
        
        // define the test-files
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
        $model_transform_gtiff = new UploadedFile(
            __DIR__."/../../../files/geotiff_modelTransform.tiff",
            'modelTransform.tiff',
            'image/tiff',
            null, 
            true
        );
        $missing_transform_gtiff = new UploadedFile(
            __DIR__."/../../../files/geotiff_missing_ModelTiePoint_and_ModelTransform.tiff",
            'missingTransform.tiff',
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

        // 1. testing upload of standard geoTIFF (in form of projected-Coordinate-Reference-System and EPSG-code)
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
        $this->assertEquals($overlay->attrs, ['width' => 3, 'height' => 2]);
        $response->assertJson($overlay->toArray(), $exact=false);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay->path));

        // 2. testing upload of geoTIFF using the ModelTransform-Tag
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff", [
            'geotiff' => $model_transform_gtiff,
            'volumeId' => $id
        ])
        ->assertSuccessful();

        $overlay2 = GeoOverlay::where('volume_id', $id)->where('id', $overlay->id + 1)->first();
        $this->assertNotNull($overlay2);
        $this->assertEqualsWithDelta($overlay2->top_left_lat, 46.4884400461342, 0.00001);
        $this->assertEqualsWithDelta($overlay2->top_left_lng, 11.3182638720361, 0.00001);
        $this->assertEqualsWithDelta($overlay2->bottom_right_lat, 46.5038380359582, 0.00001);
        $this->assertEqualsWithDelta($overlay2->bottom_right_lng, 11.3705327977256, 0.00001);
        $this->assertEquals($overlay2->name, 'modelTransform.tiff');
        $this->assertEquals($overlay2->browsing_layer, false);
        $this->assertEquals($overlay2->context_layer, false);
        $this->assertEquals($overlay2->attrs, ['width' => 396, 'height' => 183]);
        $response->assertJson($overlay2->toArray(), $exact=false);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay2->path));

        // 3. testing upload of geoTIFF with missing PixelScale / ModelTiePoint as well as missing ModelTransform-Tags.
        // Which means there is no way to transform the geotiff from raster- to model-space 
        $response = $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff", [
            'geotiff' => $missing_transform_gtiff,
            'volumeId' => $id
        ])
        ->assertInvalid([
            'affineTransformation' => 'The geoTIFF file does not have an affine transformation.',
        ]);

        // 4. testing upload of user-defined geoTIFF (GTModelType-Tag equals 32767)
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