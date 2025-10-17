<?php

namespace Biigle\Tests\Modules\Geo\Jobs;

use File;
use TestCase;
use FileCache;
use Illuminate\Support\Arr;
use Biigle\FileCache\GenericFile;
use Illuminate\Http\UploadedFile;
use Biigle\Modules\Geo\GeoOverlay;
use Jcupitt\Vips\Image as VipsImage;
use Illuminate\Support\Facades\Storage;
use Biigle\Modules\Geo\Jobs\TileSingleOverlay;


class TileSingleOverlayTest extends TestCase
{
    public function testImageNormalization()
    {
        [$img, $min, $max] = $this->createRandomImage();

        $getPixel = fn($img) => $img->getpoint(2, 3)[0];

        $normMin = 0;
        $normMax = 255;
        $pixel = $getPixel($img);
        $normPixel = round(($pixel - $min) * 255 / ($max - $min), 3);

        $overlay = GeoOverlay::factory()->create();
        $job = new TileSingleOverlay($overlay, 'test', 'test');
        $normImg = $job->imageNormalization($img, $min, $max);

        $this->assertEquals($normMin, $normImg->min());
        $this->assertEquals($normMax, round($normImg->max(), 3));
        $this->assertEquals($normPixel, round($getPixel($normImg), 3));
    }
    public function testGenerateOverlayTiles()
    {
        $overlay = GeoOverlay::factory()->create();
        $file = new GenericFile("test");

        $job = new TileSingleOverlayStub($overlay, "test", "test");
        $job->generateTiles($file, "test");

        $files = [
            $job->tempPath . "/ImageProperties.xml",
            $job->tempPath . "/TileGroup0/0-0-0.jpg",
            $job->tempPath . '/vips-properties.xml'
        ];

        $this->assertCount(3, File::allFiles($job->tempPath));
        $this->assertSame($files, array_map(fn($f) => $f->getPathname(), File::allFiles($job->tempPath)));
    }

    public function testGenerateOverlayTilesWithNormalization()
    {
        $overlay = GeoOverlay::factory()->create();
        $file = new GenericFile("test");

        $job = new TileSingleOverlayStub($overlay, "test", "test");
        $job->shouldNormalize = true;
        $job->generateTiles($file, "test");

        $files = [
            $job->tempPath . "/ImageProperties.xml",
            $job->tempPath . "/TileGroup0/0-0-0.jpg",
            $job->tempPath . '/vips-properties.xml'
        ];

        $this->assertCount(3, File::allFiles($job->tempPath));
        $this->assertSame($files, array_map(fn($f) => $f->getPathname(), File::allFiles($job->tempPath)));

    }

    public function testUploadOverlayToStorage()
    {
        config(['geo.tiles.overlay_storage_disk' => 'geo-overlays']);
        $overlay = GeoOverlay::factory()->create();

        $targetPath = "{$overlay->id}/{$overlay->id}_tiles";
        $job = new TileSingleOverlayStub($overlay, config('geo.tiles.overlay_storage_disk'), $targetPath);
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

    protected function createRandomImage()
    {
        $rows = $cols = 5;
        $data = [];

        for ($i = 0; $i < $rows; $i++) {
            $row = [];
            for($j = 0; $j < $cols; $j++) {
                $row[] = rand(0,255);
            }
            $data[] = $row;
        }

        $img = VipsImage::newFromArray($data);
        $values = Arr::flatten($data);

        return [$img, min($values), max($values)];

    }
}

class TileSingleOverlayStub extends TileSingleOverlay
{
    public $shouldNormalize = false;

    protected function getVipsImage($path)
    {
        $imageSize = 5;

        if ($this->shouldNormalize) {
            $rows = $imageSize;
            $data = [];

            // add negative number to apply normalization
            for ($i = 0; $i < $rows; $i++) {
                $data[] = [-255, 0, 0, 0, 0];
            }

            return VipsImage::newFromArray($data);
        }

        return VipsImage::black($imageSize, $imageSize);
    }
}
