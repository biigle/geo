<?php

namespace Biigle\Tests\Modules\Geo\Jobs;

use Biigle\Modules\Geo\Jobs\TileSingleOverlay;
use Biigle\Tests\Modules\Geo\GeoOverlayTest;
use Biigle\FileCache\GenericFile;
use File;
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
        $overlay = GeoOverlayTest::create();

        // save fake UploadedFile to geo-overlay storage
        $overlayFile = UploadedFile::fake()->create($overlay->name, 20, 'image/tiff');
        $overlay->storeFile($overlayFile);
        $this->assertTrue(Storage::disk('geo-overlays')->exists($overlay->path));
        
        // retreive fake UploadedFile from geo-overlay storage and cast to GenericFile
        $disk = config('geo.tiles.overlay_storage_disk');
        $file = new GenericFile("{$disk}://{$overlay->path}");
        
        $job = new TileSingleOverlayStub($overlay);
        $mock = Mockery::mock(Image::class);
        $mock->shouldReceive('dzsave')
            ->once()
            ->with($job->tempPath, [
                'layout' => 'zoomify',
                'container' => 'fs',
                'strip' => true,
            ]);

        $job->mock = $mock;

        $job->generateTiles($file, '');
    }

    public function testUploadOverlayToStorage()
    {
        config(['geo.tiles.overlay_storage_disk' => 'tiles']);
        $overlay = GeoOverlayTest::create();
        $fragment = $overlay->id;
        $job = new TileSingleOverlayStub($overlay);
        Storage::makeDirectory($job->tempPath);
        Storage::put("{$job->tempPath}/test.txt", 'test');
        $this->assertTrue(Storage::disk($job->tempPath)->exists("{$job->tempPath}/test.txt"));

        // try {
        //     Storage::fake('tiles');
        //     $job->uploadToStorage();
        //     Storage::disk('tiles')->assertExists($fragment);
        //     Storage::disk('tiles')->assertExists("{$fragment}/test.txt");
        // } finally {
        //     File::deleteDirectory($job->tempPath);
        // }
    }
}

class TileSingleOverlayStub extends TileSingleOverlay
{
    protected function getVipsImage($path)
    {
        return $this->mock;
    }
}
