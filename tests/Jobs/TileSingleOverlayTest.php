<?php

namespace Biigle\Tests\Modules\Geo\Jobs;

use Biigle\Modules\Geo\Database\factories\GeoOverlayFactory;
use Biigle\Modules\Geo\Jobs\TileSingleOverlay;
use Biigle\Tests\Modules\Geo\GeoOverlayTest;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\FileCache\GenericFile;
use File;
use FileCache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Jcupitt\Vips\Image;
use Mockery;
use TestCase;

class TileSingleOverlayTest extends TestCase
{
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
}

class TileSingleOverlayStub extends TileSingleOverlay
{
    protected function getVipsImage($path)
    {
        return $this->mock;
    }
}
