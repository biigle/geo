<?php

namespace Biigle\Tests\Modules\Geo\Jobs;

use File;
use TestCase;
use Biigle\User;
use Illuminate\Support\Arr;
use Biigle\FileCache\GenericFile;
use Biigle\Modules\Geo\GeoOverlay;
use Jcupitt\Vips\Image as VipsImage;
use Illuminate\Support\Facades\Storage;
use Biigle\Modules\Geo\Jobs\TileSingleOverlay;



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
        $job->edgeCase = true;
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
}

class TileSingleOverlayStub extends TileSingleOverlay
{

    public $useGrayImage = false;

    public $edgeCase = false;

    public $outputImg;

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

            if ($this->edgeCase) {
                $pixels[0][1] = 90;
            }

            return VipsImage::newFromArray($pixels);
        }

        return VipsImage::black(5, 5);
    }
}
