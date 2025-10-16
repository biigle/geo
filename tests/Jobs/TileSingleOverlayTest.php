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
    public function testMinMax()
    {
        [$img1, $min, $max] = $this->createRandomImage();
        $img2 = VipsImage::black(10, 10);

        $this->assertEquals($min, TileSingleOverlay::min($img1));
        $this->assertEquals($max, TileSingleOverlay::max($img1));
        $this->assertEquals(0, TileSingleOverlay::min($img2));
        $this->assertEquals(0, TileSingleOverlay::max($img2));
    }

    public function testImageNormalization()
    {
        [$img, $min, $max] = $this->createRandomImage();

        $getPixel = fn($img) => $img->getpoint(2, 3)[0];

        $normMin = 0;
        $normMax = 255;
        $pixel = $getPixel($img);
        $normPixel = round(($pixel - $min) * 255 / ($max - $min), 3);

        $normImg = TileSingleOverlay::imageNormalization($img, $min, $max);

        $this->assertEquals($normMin, TileSingleOverlay::min($normImg));
        $this->assertEquals($normMax, round(TileSingleOverlay::max($normImg), 3));
        $this->assertEquals($normPixel, round($getPixel($normImg), 3));
    }
    public function testGenerateOverlayTiles()
    {
        Storage::fake('geo-overlays');
        $overlay = GeoOverlay::factory()->create();


        // save fake UploadedFile to geo-overlay storage
        // $overlayFile = UploadedFile::fake()->create($overlay->name, 20, 'image/tiff');
        $overlayFile = new UploadedFile(
            __DIR__."/../files/geotiff_standardEPSG2013.tif",
            'standardEPSG2013.tif',
            'image/tiff',
            null, 
            true
        );

        $disk = config('geo.tiles.overlay_storage_disk');
        $overlay->storeFile($overlayFile);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay->path));
        // $testCorrectFile = Storage::disk('geo-overlays')->path($overlay->path);
        // dd("{$disk}://{$overlay->path}", $testCorrectFile);
        
        
        // retreive UploadedFile from geo-overlay storage and cast to GenericFile
        $file = new GenericFile("{$disk}://{$overlay->path}");
        $targetPath = "{$overlay->id}/{$overlay->id}_tiles";
        $job = new TileSingleOverlay($overlay, $disk, $targetPath);

        FileCache::getOnce($file, [$job, 'generateTiles']);
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
    protected function getVipsImage($path)
    {
        return $this->mock;
    }
}
