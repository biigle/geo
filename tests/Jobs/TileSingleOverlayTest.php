<?php

namespace Biigle\Tests\Modules\Geo\Jobs;

use Biigle\FileCache\GenericFile;
use Biigle\Modules\Geo\Events\GeoTiffUploadFailed;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Modules\Geo\Jobs\TileSingleOverlay;
use Biigle\User;
use Exception;
use File;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Jcupitt\Vips\Image as VipsImage;
use TestCase;



class TileSingleOverlayTest extends TestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

    }

    public function testGenerateOverlayTiles()
    {
        $overlay = GeoOverlay::factory()->create();
        $file = new GenericFile("test");

        $job = new TileSingleOverlayStub($overlay, $this->user, []);
        $job->generateTiles($file, "test");

        $files = [
            $job->tempPath . "/ImageProperties.xml",
            $job->tempPath . "/TileGroup0/0-0-0.png",
            $job->tempPath . '/vips-properties.xml'
        ];

        $this->assertCount(3, File::allFiles($job->tempPath));
        $this->assertSame($files, array_map(fn($f) => $f->getPathname(), File::allFiles($job->tempPath)));
    }

    public function testGenerateOverlayTilesInvalidColorspace()
    {
        $overlay = GeoOverlay::factory()->create();
        $file = new GenericFile("test");

        Event::fake();
        $job = new TileSingleOverlayStub($overlay, $this->user, []);
        $job->invalidColorspace = true;
        $job->withFakeQueueInteractions();
        $job->generateTiles($file, "test");

        $job->assertFailed();
        Event::assertDispatched(GeoTiffUploadFailed::class);
        $this->assertFalse(GeoOverlay::where('id', $overlay->id)->exists());
    }

    public function testGenerateOverlayTilesThrowException()
    {
        $overlay = GeoOverlay::factory()->create();

        Event::fake();
        $job = new TileSingleOverlayStub($overlay, $this->user, []);
        $job->failed(new Exception());

        Event::assertDispatched(GeoTiffUploadFailed::class);
        $this->assertFalse(GeoOverlay::where('id', $overlay->id)->exists());
    }

    public function testGenerateOverlayTilesWithNormalization()
    {
        $file = new GenericFile("test");
        $overlay = GeoOverlay::factory()->create();
        $job = new TileSingleOverlayStub($overlay, $this->user, []);
        $job->useGrayImage = true;
        $job->generateTiles($file, "test");
        $getPixel = fn($img) => $img->getpoint(2, 3)[0];
        $normImg = $job->outputImg;

        $files = [
            $job->tempPath . "/ImageProperties.xml",
            $job->tempPath . "/TileGroup0/0-0-0.png",
            $job->tempPath . '/vips-properties.xml'
        ];

        $this->assertEquals(0, $normImg->min());
        $this->assertEquals(255, $normImg->max());
        $this->assertEquals(54, $getPixel($normImg));
        $this->assertCount(3, File::allFiles($job->tempPath));
        $this->assertSame($files, array_map(fn($f) => $f->getPathname(), File::allFiles($job->tempPath)));
    }

    public function testGenerateOverlayTilesWithNormalizationEdgeCase()
    {
        $file = new GenericFile("test");
        $getPixel = fn($img) => $img->getpoint(2, 3)[0];
        $overlay = GeoOverlay::factory()->create();
        $job = new TileSingleOverlayStub($overlay, $this->user, []);
        $job->useGrayImage = true;
        $job->edgeCase = 90;
        $job->generateTiles($file, "test");
        $normImg = $job->outputImg;

        $files = [
            $job->tempPath . "/ImageProperties.xml",
            $job->tempPath . "/TileGroup0/0-0-0.png",
            $job->tempPath . '/vips-properties.xml'
        ];

        $this->assertEquals(0, $normImg->min());
        $this->assertEquals(255, $normImg->max());
        $this->assertEquals(60, $getPixel($normImg));
        $this->assertCount(3, File::allFiles($job->tempPath));
        $this->assertSame($files, array_map(fn($f) => $f->getPathname(), File::allFiles($job->tempPath)));
    }

    public function testUploadOverlayToStorage()
    {
        config(['geo.tiles.overlay_storage_disk' => 'geo-overlays']);
        $overlay = GeoOverlay::factory()->create();

        $targetPath = "{$overlay->id}/{$overlay->id}_tiles";
        $job = new TileSingleOverlayStub($overlay, $this->user, []);
        File::makeDirectory($job->tempPath);
        File::put("{$job->tempPath}/test.txt", 'test');

        try {
            Storage::fake('geo-overlays');
            $job->uploadToStorage();
            Storage::disk('geo-overlays')->assertExists($targetPath);
            Storage::disk('geo-overlays')->assertExists("{$targetPath}/test.txt");
        } finally {
            File::deleteDirectory($job->tempPath);
        }
    }

    public function testGenerateOverlayTilesWithNormalizationWithNoDataValue()
    {
        $file = new GenericFile("test");

        $overlay = GeoOverlay::factory()->create();
        $job = new TileSingleOverlayStub($overlay, $this->user, ['IFD0:GDALNoData' => 0]);
        $job->useGrayImage = true;
        $job->generateTiles($file, "test");
        $getPixel = fn($img) => $img->getpoint(2, 3)[0];
        $normImg = $job->outputImg;

        $files = [
            $job->tempPath . "/ImageProperties.xml",
            $job->tempPath . "/TileGroup0/0-0-0.png",
            $job->tempPath . '/vips-properties.xml'
        ];

        $this->assertEquals(0, $normImg->min());
        $this->assertEquals(255, $normImg->max());
        $this->assertEquals(49, $getPixel($normImg));
        $this->assertCount(3, File::allFiles($job->tempPath));
        $this->assertSame($files, array_map(fn($f) => $f->getPathname(), File::allFiles($job->tempPath)));
    }

    public function testGenerateOverlayTilesCreateAlphaMask()
    {
        $file = new GenericFile("test");

        $overlay = GeoOverlay::factory()->create();
        $job = new TileSingleOverlayStub($overlay, $this->user, ['IFD0:GDALNoData' => -9999]);
        $job->useGrayImage = true;
        $job->edgeCase = -9999;
        $job->generateTiles($file, "test");
        $getPixel = fn($img) => $img->getpoint(2, 3)[0];
        $normImg = $job->outputImg;
        $mask = $normImg->extract_band(1);
        $mask_hist = $mask->hist_find()->writeToArray();

        $files = [
            $job->tempPath . "/ImageProperties.xml",
            $job->tempPath . "/TileGroup0/0-0-0.png",
            $job->tempPath . '/vips-properties.xml'
        ];

        $this->assertTrue($normImg->hasAlpha());
        $this->assertEquals(0, $normImg->getpoint(1, 0)[1]);
        $this->assertEquals(1, $mask_hist[0]);
        $this->assertEquals(0, $normImg->min());
        $this->assertEquals(255, $normImg->max());
        $this->assertEquals(60, $getPixel($normImg));
        $this->assertCount(3, File::allFiles($job->tempPath));
        $this->assertSame($files, array_map(fn($f) => $f->getPathname(), File::allFiles($job->tempPath)));
    }

}

class TileSingleOverlayStub extends TileSingleOverlay
{

    public $useGrayImage = false;

    public $edgeCase = 0;

    public $outputImg;

    public $invalidColorspace = false;

    public function generateTiles($file, $path)
    {
        parent::generateTiles($file, $path);
        $this->outputImg = $this->vipsImage;
    }

    protected function getVipsImage($path)
    {
        if ($this->useGrayImage) {
            $pixels = [
                [0, 100, 36, 29, 13],
                [46, 50, 2, 71, 5],
                [68, 59, 85, 86, 66],
                [52, 89, 21, 68, 71],
            ];

            if ($this->edgeCase != 0) {
                $pixels[0][1] = $this->edgeCase;
            }

            return VipsImage::newFromArray($pixels);
        }

        if ($this->invalidColorspace) {
            return VipsImage::black(5, 5, ['bands' => 5]);
        }

        return VipsImage::black(5, 5);
    }
}
