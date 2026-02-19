<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use Mockery;
use Storage;
use Exception;
use ApiTestCase;
use Biigle\MediaType;
use Illuminate\Http\UploadedFile;
use Biigle\Modules\Geo\GeoOverlay;
use Illuminate\Support\Facades\Queue;
use Biigle\Modules\Geo\Jobs\TileSingleOverlay;
use Biigle\Modules\Geo\Services\Support\GeoManager;
use Biigle\Modules\Geo\Exceptions\TransformCoordsException;

class GeoTiffOverlayControllerTest extends ApiTestCase
{
    public $mock = null;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('geo-overlays');
        Queue::fake();
        $this->mock = Mockery::mock(GeoManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->app->bind(GeoManager::class, fn() => $this->mock);
    }

    public function testStoreGeotiff()
    {
        $id = $this->volume()->id;
        $exif = [
            "System:FileName" => "geotiff_standardEPSG2013.tif",
            "IFD0:ImageWidth" => 3,
            "IFD0:ImageHeight" => 2,
            "IFD0:PixelScale" => "57 53.125 0",
            "IFD0:ModelTiePoint" => "0 0 0 344275.42 801113.187 0",
            "GeoTiff:GTModelType" => 1,
            "GeoTiff:ProjectedCSType" => 27700,
        ];
        $file = UploadedFile::fake()->create('standardEPSG2013.tif', 1, 'image/tiff');

        $this->doTestApiRoute('POST', "/api/v1/volumes/{$id}/geo-overlays/geotiff");

        $this->beEditor();
        // 403: The client does not have access rights to the content
        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors.
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff")
            ->assertInvalid(['geotiff', 'layer_index']);

        $emptyFile = UploadedFile::fake()->create('overlay.tif', 0, 'image/tiff');
        // check if "empty" geotiff fails properly
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff", [
            'geotiff' => $emptyFile,
            'layer_index' => 0
            ])
            ->assertInvalid('size');

        $this->assertFalse(GeoOverlay::exists());

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);

        $response = $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertSuccessful();

        Queue::assertPushed(TileSingleOverlay::class);
        $overlay = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay);
        $this->assertSame(57.0966514849888, $overlay['attrs']['top_left_lat']);
        $this->assertSame(-2.9213164328107, $overlay['attrs']['top_left_lng']);
        $this->assertSame(57.0976265261217, $overlay['attrs']['bottom_right_lat']);
        $this->assertSame(-2.9185182540292, $overlay['attrs']['bottom_right_lng']);
        $this->assertSame(3, $overlay['attrs']['width']);
        $this->assertSame(2, $overlay['attrs']['height']);
        $this->assertSame($overlay['type'], 'geotiff');
        $this->assertEquals(0, $overlay['layer_index']);
        $this->assertSame($overlay['name'], 'standardEPSG2013.tif');
        $this->assertTrue($overlay['browsing_layer']);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay['id']));
    }

    public function testStoreGeoTiffModelTransformTag()
    {
        $id = $this->volume()->id;
        $this->beAdmin();
        $exif = [
            "System:FileName" => "geotiff_modelTransform.tiff",
            "IFD0:ImageWidth" => 396,
            "IFD0:ImageHeight" => 183,
            "IFD0:ModelTransform" => "10 0 0 677920 0 10 0 5150930 0 0 0 0 0 0 0 1",
            "GeoTiff:GTModelType" => 1,
            "GeoTiff:GTRasterType" => 1,
            "GeoTiff:ProjectedCSType" => 32632,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_modelTransform.tiff', 1, 'image/tiff');

        $response = $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertSuccessful();

        Queue::assertPushed(TileSingleOverlay::class);
        $overlay2 = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay2);
        $this->assertSame(46.4884400488204, $overlay2['attrs']['top_left_lat']);
        $this->assertSame(11.3182638714155, $overlay2['attrs']['top_left_lng']);
        $this->assertSame(46.503838038652, $overlay2['attrs']['bottom_right_lat']);
        $this->assertSame(11.3705327969833, $overlay2['attrs']['bottom_right_lng']);
        $this->assertSame(396, $overlay2['attrs']['width']);
        $this->assertSame(183, $overlay2['attrs']['height']);
        $this->assertSame('geotiff', $overlay2['type']);
        $this->assertSame('geotiff_modelTransform.tiff', $overlay2['name']);
        $this->assertTrue($overlay2['browsing_layer']);
        $this->assertEquals(0, $overlay2['layer_index']);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay2['id']));
    }

    public function testStoreGeotiffWrapPointCoords()
    {
        $id = $this->volume()->id;
        $this->beAdmin();
        //                                                                        _
        // Coordinates point to the map's wrapping point (case: LL = _| and UR = | )
        $exif = [
            'IFD0:ImageWidth' => 5490,
            'IFD0:ImageHeight' => 5490,
            'IFD0:PixelScale' => '20 20 0',
            'IFD0:ModelTiePoint' => '0 0 0 300000 3100000 0',
            'IFD0:GDALNoData' => 0,
            'GeoTiff:GTModelType' => 1,
            'GeoTiff:GTRasterType' => 1,
            'GeoTiff:ProjectedCSType' => 32701,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_wrap_coords.tiff', 1, 'image/tiff');

        $response = $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertSuccessful();

        Queue::assertPushed(TileSingleOverlay::class);
        $overlay2 = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay2);
        $this->assertSame(-63.1617421941426, $overlay2['attrs']['top_left_lat']);
        $this->assertSame(179.02766304111955, $overlay2['attrs']['top_left_lng']);
        $this->assertSame(-62.2209238067761, $overlay2['attrs']['bottom_right_lat']);
        $this->assertSame(181.2651555508601, $overlay2['attrs']['bottom_right_lng']);
        $this->assertSame(5490, $overlay2['attrs']['width']);
        $this->assertSame(5490, $overlay2['attrs']['height']);
        $this->assertSame('geotiff', $overlay2['type']);
        $this->assertSame('geotiff_wrap_coords.tiff', $overlay2['name']);
        $this->assertTrue($overlay2['browsing_layer']);
        $this->assertEquals(0, $overlay2['layer_index']);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay2['id']));
    }

    public function testStoreGeotiffPixelScaleIllDefined()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        $exif = [
            'IFD0:ImageWidth' => 5490,
            'IFD0:ImageHeight' => 5490,
            'IFD0:PixelScale' => '0 0 0',
            'IFD0:ModelTiePoint' => '0 0 0 300000 3100000 0',
            'IFD0:GDALNoData' => 0,
            'GeoTiff:GTModelType' => 1,
            'GeoTiff:GTRasterType' => 1,
            'GeoTiff:ProjectedCSType' => 32701,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_wrap_coords.tiff', 1, 'image/tiff');

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid('affineTransformation');

        Queue::assertNotPushed(TileSingleOverlay::class);
    }

    public function testStoreGeotiffMissingData()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        // Missing PixelScale
        $exif = [
            'IFD0:ImageWidth' => 5490,
            'IFD0:ImageHeight' => 5490,
            'IFD0:ModelTiePoint' => '0 0 0 300000 3100000 0',
            'IFD0:GDALNoData' => 0,
            'GeoTiff:GTModelType' => 1,
            'GeoTiff:GTRasterType' => 1,
            'GeoTiff:ProjectedCSType' => 32701,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_wrap_coords.tiff', 1, 'image/tiff');

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid('affineTransformation');

        Queue::assertNotPushed(TileSingleOverlay::class);

        // Missing ModelTiePoint
        $exif = [
            'IFD0:ImageWidth' => 5490,
            'IFD0:ImageHeight' => 5490,
            'IFD0:PixelScale' => '0 0 0',
            'IFD0:GDALNoData' => 0,
            'GeoTiff:GTModelType' => 1,
            'GeoTiff:GTRasterType' => 1,
            'GeoTiff:ProjectedCSType' => 32701,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_wrap_coords.tiff', 1, 'image/tiff');

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid('affineTransformation');

        Queue::assertNotPushed(TileSingleOverlay::class);

        // No transform data given
        $exif = [
            "IFD0:ImageWidth" => 396,
            "IFD0:ImageHeight" => 183,
            "GeoTiff:GTModelType" => 1,
            "GeoTiff:ProjectedCSType" => 32632,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_modelTransform.tiff', 1, 'image/tiff');

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid(['affineTransformation']);
        Queue::assertNothingPushed();
    }

    public function testStoreGeotiffPrioritizeModelTransform()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        // Pixelscale is ill-defined, so use ModelTransform instead
        $exif = [
            "System:FileName" => "geotiff_modelTransform.tiff",
            "IFD0:ImageWidth" => 396,
            "IFD0:ImageHeight" => 183,
            'IFD0:PixelScale' => '0 0 0',
            'IFD0:ModelTiePoint' => '0 0 0 300000 3100000 0',
            "IFD0:ModelTransform" => "10 0 0 677920 0 10 0 5150930 0 0 0 0 0 0 0 1",
            "GeoTiff:GTModelType" => 1,
            "GeoTiff:GTRasterType" => 1,
            "GeoTiff:ProjectedCSType" => 32632,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_modelTransform.tiff', 1, 'image/tiff');

        $response = $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertSuccessful();

        Queue::assertPushed(TileSingleOverlay::class);
        $overlay2 = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay2);
        $this->assertSame(46.4884400488204, $overlay2['attrs']['top_left_lat']);
        $this->assertSame(11.3182638714155, $overlay2['attrs']['top_left_lng']);
        $this->assertSame(46.503838038652, $overlay2['attrs']['bottom_right_lat']);
        $this->assertSame(11.3705327969833, $overlay2['attrs']['bottom_right_lng']);
        $this->assertSame(396, $overlay2['attrs']['width']);
        $this->assertSame(183, $overlay2['attrs']['height']);
        $this->assertSame('geotiff', $overlay2['type']);
        $this->assertSame('geotiff_modelTransform.tiff', $overlay2['name']);
        $this->assertTrue($overlay2['browsing_layer']);
        $this->assertEquals(0, $overlay2['layer_index']);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay2['id']));
    }


    public function testStoreGeotiffWGS84()
    {
        $id = $this->volume()->id;
        $this->beAdmin();
        // Allow geographic CRS if code EPSG 4326 is used
        $exif = [
            'IFD0:ImageWidth' => 768,
            'IFD0:ImageHeight' => 608,
            'IFD0:ModelTransform' => '0.0132309081041667 0 0 5.554322947 0 -0.0132389576726974 0 55.118670158 0 0 0 0 0 0 0 1',
            'IFD0:GDALNoData' => 0.0,
            'GeoTiff:GTModelType' => 2,
            'GeoTiff:GTRasterType' => 1,
            'GeoTiff:GeographicType' => 4326,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_wgs84.tiff', 1, 'image/tiff');

        $response = $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        );
        $response->assertSuccessful();

        Queue::assertPushed(TileSingleOverlay::class);
        $overlay2 = json_decode($response->getContent(), true);
        $this->assertNotNull($overlay2);
        $this->assertSame(47.069383893, $overlay2['attrs']['top_left_lat']);
        $this->assertSame(5.554322947, $overlay2['attrs']['top_left_lng']);
        $this->assertSame(55.118670158, actual: $overlay2['attrs']['bottom_right_lat']);
        $this->assertSame(15.715660371, $overlay2['attrs']['bottom_right_lng']);
        $this->assertSame(768, $overlay2['attrs']['width']);
        $this->assertSame(608, $overlay2['attrs']['height']);
        $this->assertSame('geotiff', $overlay2['type']);
        $this->assertSame('geotiff_wgs84.tiff', $overlay2['name']);
        $this->assertTrue($overlay2['browsing_layer']);
        $this->assertEquals(0, $overlay2['layer_index']);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay2['id']));
    }

    public function testStoreGeotiffTransformErr()
    {
        $id = $this->volume()->id;
        $this->beAdmin();
        $exif = [
            'IFD0:ImageWidth' => 768,
            'IFD0:ImageHeight' => 608,
            'IFD0:ModelTransform' => '0.0132309081041667 0 0 5.554322947 0 -0.0132389576726974 0 55.118670158 0 0 0 0 0 0 0 1',
            'IFD0:GDALNoData' => 0.0,
            'GeoTiff:GTModelType' => 1,
            'GeoTiff:GTRasterType' => 1,
            'GeoTiff:ProjectedCSType' => 32701,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $this->mock->shouldReceive('transformToEPSG4326')->andThrow(new TransformCoordsException());
        $file = UploadedFile::fake()->create('geotiff_modelTransform.tiff', 1, 'image/tiff');

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid(['failedTransformation']);
        Queue::assertNothingPushed();
    }

    public function testStoreGeotiffAnyException()
    {
        $id = $this->volume()->id;
        $this->beAdmin();
        $exif = [
            'IFD0:ImageWidth' => 768,
            'IFD0:ImageHeight' => 608,
            'IFD0:ModelTransform' => '0.0132309081041667 0 0 5.554322947 0 -0.0132389576726974 0 55.118670158 0 0 0 0 0 0 0 1',
            'IFD0:GDALNoData' => 0.0,
            'GeoTiff:GTModelType' => 1,
            'GeoTiff:GTRasterType' => 1,
            'GeoTiff:ProjectedCSType' => 32701,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $this->mock->shouldReceive('convertToModelSpace')->andThrow(new Exception());
        $file = UploadedFile::fake()->create('geotiff_modelTransform.tiff', 1, 'image/tiff');

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid(['failedUpload']);
        Queue::assertNothingPushed();
    }

    public function testStoreInvalidLayerIndex()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        $this->mock->shouldReceive('getExifData')->never();
        $file = UploadedFile::fake()->create('geotiff_modelTransform.tiff', 1, 'image/tiff');

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 99
            ]
        )->assertInvalid(['layer_index']);
        Queue::assertNothingPushed();
    }

    public function testStoreGeotiffMissingModelType()
    {
        $id = $this->volume()->id;
        $this->beAdmin();
        $exif = [
            'IFD0:ImageWidth' => 768,
            'IFD0:ImageHeight' => 608,
            'IFD0:GDALNoData' => 0.0,
            'GeoTiff:GTRasterType' => 1,
            'GeoTiff:ProjectedCSType' => 32701,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_color.tiff', 1, 'image/tiff');

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid(['MissingModelType']);
        Queue::assertNothingPushed();
    }

    public function testStoreGeotiffWrongModelType()
    {
        $id = $this->volume()->id;
        $this->beAdmin();
        $exif = [
            'IFD0:ImageWidth' => 768,
            'IFD0:ImageHeight' => 608,
            'GeoTiff:GTModelType' => 3,
            'GeoTiff:GTRasterType' => 1,
            'GeoTiff:GeocentricType' => 32701,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_modelTransform.tiff', 1, 'image/tiff');

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid(['wrongModelType', 'noPCSKEY']);
        Queue::assertNothingPushed();
    }

    public function testStoreGeotiffUndefinedCRS()
    {
        $id = $this->volume()->id;
        $this->beAdmin();
        $exif = [
            'IFD0:ImageWidth' => 768,
            'IFD0:ImageHeight' => 608,
            'IFD0:ModelTransform' => '0.0132309081041667 0 0 5.554322947 0 -0.0132389576726974 0 55.118670158 0 0 0 0 0 0 0 1',
            'IFD0:GDALNoData' => 0.0,
            'GeoTiff:GTModelType' => 1,
            'GeoTiff:GTRasterType' => 1,
            'GeoTiff:ProjectedCSType' => 0,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_modelTransform.tiff', 1, 'image/tiff');

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid(['unDefined']);
        Queue::assertNothingPushed();
    }

    public function testStoreGeotiffInvalidColorSpace()
    {
        $id = $this->volume()->id;
        $this->beAdmin();
        $exif = [
            'IFD0:ImageWidth' => 768,
            'IFD0:ImageHeight' => 608,
            'IFD0:ModelTransform' => '0.0132309081041667 0 0 5.554322947 0 -0.0132389576726974 0 55.118670158 0 0 0 0 0 0 0 1',
            'IFD0:GDALNoData' => 0.0,
            'GeoTiff:GTModelType' => 1,
            'GeoTiff:GTRasterType' => 1,
            'GeoTiff:ProjectedCSType' => 32701,
            'IFD0:SamplesPerPixel' => 5
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $this->mock->shouldReceive('convertToModelSpace')->andThrow(new Exception());
        $file = UploadedFile::fake()->create('geotiff_modelTransform.tiff', 1, 'image/tiff');

        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid(['invalidColorSpace']);
        Queue::assertNothingPushed();
    }

    public function testStoreGeotiffCustomCode()
    {
        $id = $this->volume()->id;
        $this->beAdmin();
        $exif = [
            "GeoTiff:GTModelType" => 1,
            "GeoTiff:ProjectedCSType" => 32767,
        ];

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);
        $file = UploadedFile::fake()->create('geotiff_modelTransform.tiff', 1, 'image/tiff');


        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid(['userDefined']);
        Queue::assertNothingPushed();
    }

    public function testStoreDupliate()
    {
        $id = $this->volume()->id;
        $this->beAdmin();

        GeoOverlay::factory()->create([
            'volume_id' => $id,
            'name' => 'test123.tiff'
        ]);

        $file = UploadedFile::fake()->create('test123.tiff', 1, 'image/tiff');
        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid(['fileExists']);
        Queue::assertNothingPushed();
    }

    public function testStoreVideoVolume()
    {
        $id = $this->volume(['media_type_id' => MediaType::videoId()])->id;
        $file = UploadedFile::fake()->create('overlay.tif');
        $this->beAdmin();
        $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
                'layer_index' => 0
            ]
        )->assertInvalid('id');
        Queue::assertNothingPushed();
    }
}
