<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\MediaType;
use Biigle\Modules\Geo\GeoOverlay;
use Illuminate\Http\UploadedFile;
use Storage;

class GeoTiffOverlayControllerTest extends ApiTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('geo-overlays');
    }

    public function testStoreGeotiff()
    {
        $id = $this->volume()->id;

        // define the test-files
        $standard_gtiff = new UploadedFile(
            __DIR__ . "/../../../files/geotiff_standardEPSG2013.tif",
            'standardEPSG2013.tif',
            'image/tiff',
            null,
            true
        );

        $this->doTestApiRoute('POST', "/api/v1/volumes/{$id}/geo-overlays/geotiff");

        $this->beEditor();
        // 403: The client does not have access rights to the content
        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $standard_gtiff,
                'volumeId' => $id
            ]
        )->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors.
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff")
            ->assertStatus(422);

        $file = UploadedFile::fake()->create('overlay.tif');
        // check if "empty" geotiff fails properly
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff", ['geotiff' => $file])
            ->assertStatus(422);

        $this->assertFalse(GeoOverlay::exists());

        $response = $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $standard_gtiff,
                'volumeId' => $id
            ]
        )->assertSuccessful();

        $overlay = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay);
        $this->assertSame(57.0966514849888, $overlay['attrs']['top_left_lat']);
        $this->assertSame(-2.9213164328107, $overlay['attrs']['top_left_lng']);
        $this->assertSame(57.0976265261217, $overlay['attrs']['bottom_right_lat']);
        $this->assertSame(-2.9185182540292, $overlay['attrs']['bottom_right_lng']);
        $this->assertSame(3, $overlay['attrs']['width']);
        $this->assertSame(2, $overlay['attrs']['height']);
        $this->assertSame($overlay['type'], 'geotiff');
        $this->assertSame($overlay['layer_index'], null);
        $this->assertSame($overlay['name'], 'standardEPSG2013.tif');
        $this->assertFalse($overlay['browsing_layer']);
        $this->assertFalse($overlay['context_layer']);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay['id']));
    }

    public function testStoreGeoTiffModelTransformTag()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        $model_transform_gtiff = new UploadedFile(
            __DIR__ . "/../../../files/geotiff_modelTransform.tiff",
            'modelTransform.tiff',
            'image/tiff',
            null,
            true
        );

        $response = $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $model_transform_gtiff,
                'volumeId' => $id
            ]
        )->assertSuccessful();

        $overlay2 = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay2);
        $this->assertSame(46.4884400488204, $overlay2['attrs']['top_left_lat']);
        $this->assertSame(11.3182638714155, $overlay2['attrs']['top_left_lng']);
        $this->assertSame(46.503838038652, $overlay2['attrs']['bottom_right_lat']);
        $this->assertSame(11.3705327969833, $overlay2['attrs']['bottom_right_lng']);
        $this->assertSame(396, $overlay2['attrs']['width']);
        $this->assertSame(183, $overlay2['attrs']['height']);
        $this->assertSame('geotiff', $overlay2['type']);
        $this->assertSame('modelTransform.tiff', $overlay2['name']);
        $this->assertFalse($overlay2['browsing_layer']);
        $this->assertFalse($overlay2['context_layer']);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay2['id']));
    }

    public function testStoreGeotiffMissingData()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        $missing_transform_gtiff = new UploadedFile(
            __DIR__ . "/../../../files/geotiff_missing_ModelTiePoint_and_ModelTransform.tiff",
            'missingTransform.tiff',
            'image/tiff',
            null,
            true
        );

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $missing_transform_gtiff,
                'volumeId' => $id
            ]
        )->assertInvalid(['affineTransformation']);
    }

    public function testStoreGeotiffCustomCode()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        $user_defined_gtiff = new UploadedFile(
            __DIR__ . "/../../../files/geotiff_user_defined2011.tif",
            'user_defined2011.tif',
            'image/tiff',
            null,
            true
        );

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $user_defined_gtiff,
                'volumeId' => $id
            ]
        )->assertInvalid(['userDefined']);
    }

    public function testStoreVideoVolume()
    {
        $id = $this->volume(['media_type_id' => MediaType::videoId()])->id;
        $file = UploadedFile::fake()->create('overlay.tif');
        $this->beAdmin();
        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'file' => $file,
            ]
        )->assertStatus(422);
    }
}
