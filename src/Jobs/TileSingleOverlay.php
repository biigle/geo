<?php

namespace Biigle\Modules\Geo\Jobs;

use Biigle\FileCache\GenericFile;
use Biigle\Jobs\TileSingleImage;
use FileCache;
use Biigle\Modules\Geo\GeoOverlay;
use File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class TileSingleOverlay extends TileSingleImage implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * The overlay to generate tiles for.
     *
     * @var GeoOverlay
     */
    public $file;

    /**
     * The uploaded geoTIFF file fetched from geo.tiles.overlay_storage_disk
     *
     * @var GenericFile
     */
    public $genericFile;

    /**
     * Create a new job instance.
     *
     * @param GeoOverlay $file The Overlay to generate tiles for.
     *
     * @return void
     */
    public function __construct(GeoOverlay $file, $storage, $targetPath)
    {
        $this->file = $file;
        $this->tempPath = config('geo.tiles.tmp_dir')."/{$file->id}";
        // for uploadToStorage method
        $this->storage = $storage;
        $this->targetPath = $targetPath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->genericFile = new GenericFile("{$this->storage}://{$this->file->path}");
            FileCache::getOnce($this->genericFile, [$this, 'generateTiles']);
            $this->uploadToStorage();
            $this->file->tilingInProgress = false;
            $this->file->save();
        } finally {
            File::deleteDirectory($this->tempPath);
        }
    }
}
