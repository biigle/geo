<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use Biigle\Modules\Geo\Services\Support\GeoManager;
use Mockery;
use Storage;
use ApiTestCase;
use Biigle\MediaType;
use Illuminate\Http\UploadedFile;
use Biigle\Modules\Geo\GeoOverlay;

class GeoTiffOverlayControllerTest extends ApiTestCase
{
    public $mock = null;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('geo-overlays');
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
                'volumeId' => $id
            ]
        )->assertStatus(403);

        $this->beAdmin();
        // 422: The request was well-formed but was unable to be followed due to semantic errors.
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff")
            ->assertStatus(422);

        $emptyFile = UploadedFile::fake()->create('overlay.tif');
        // check if "empty" geotiff fails properly
        $this->postJson("/api/v1/volumes/{$id}/geo-overlays/geotiff", ['geotiff' => $emptyFile])
            ->assertStatus(422);

        $this->assertFalse(GeoOverlay::exists());

        $this->mock->shouldReceive('getExifData')->once()->andReturn($exif);

        $response = $this->postJson(
            "/api/v1/volumes/{$id}/geo-overlays/geotiff",
            [
                'geotiff' => $file,
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
        $this->assertSame('geotiff_modelTransform.tiff', $overlay2['name']);
        $this->assertTrue($overlay2['browsing_layer']);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay2['id']));
    }

    public function testStoreGeotiffMissingData()
    {
        $id = $this->volume()->id;
        $this->beAdmin();
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
                'volumeId' => $id
            ]
        )->assertInvalid(['affineTransformation']);
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
