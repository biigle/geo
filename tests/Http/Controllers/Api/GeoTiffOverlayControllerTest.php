<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\MediaType;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Tests\Modules\Geo\GeoOverlayTest;
use Illuminate\Http\UploadedFile;
use Storage;
use Illuminate\Testing\Fluent\AssertableJson;

class GeoTiffOverlayControllerTest extends ApiTestCase
{

    public function testStoreGeotiff() 
    {
        Storage::fake('geo-overlays');
        $id = $this->volume()->id;
    
        // Get current ID so we can predict the next ID later.
        $overlay = GeoOverlayTest::createGeotiffOverlay();
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
        $this->assertSame($overlay->attrs['top_left_lat'], 57.0974838573358);
        $this->assertSame($overlay->attrs['top_left_lng'], -2.9198048485707);
        $this->assertSame($overlay->attrs['bottom_right_lat'], 57.0984589659731);
        $this->assertSame($overlay->attrs['bottom_right_lng'], -2.9170062535908);
        $this->assertSame($overlay->attrs['width'], 3);
        $this->assertSame($overlay->attrs['height'], 2);
        $this->assertSame($overlay->type, 'geotiff');
        $this->assertSame($overlay->layer_index, null);
        $this->assertSame($overlay->name, 'standardEPSG2013.tif');
        $this->assertSame($overlay->browsing_layer, false);
        $this->assertSame($overlay->context_layer, false);
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
        $this->assertSame($overlay2->attrs['top_left_lat'], 46.4884400461342);
        $this->assertSame($overlay2->attrs['top_left_lng'], 11.3182638720361);
        $this->assertSame($overlay2->attrs['bottom_right_lat'], 46.5038380359582);
        $this->assertSame($overlay2->attrs['bottom_right_lng'], 11.3705327977256);
        $this->assertSame($overlay2->attrs['width'], 396,);
        $this->assertSame($overlay2->attrs['height'], 183);
        $this->assertSame($overlay->type, 'geotiff');
        $this->assertSame($overlay2->name, 'modelTransform.tiff');
        $this->assertSame($overlay2->browsing_layer, false);
        $this->assertSame($overlay2->context_layer, false);

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