<?php

namespace Biigle\Modules\Geo\Jobs;

use Biigle\FileCache\GenericFile;
use FileCache;
use Biigle\Modules\Geo\GeoOverlay;
use Exception;
use File;
use FilesystemIterator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use VipsImage;

class TileSingleOverlay extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * The overlay to generate tiles for.
     *
     * @var GeoOverlay
     */
    public $overlay;

    /**
     * Path to the temporary storage file for the tiles.
     *
     * @var string
     */
    public $tempPath;

    public $file;

    /**
     * Ignore this job if the overlay does not exist any more.
     *
     * @var bool
     */
    protected $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @param GeoOverlay $overlay The Overlay to generate tiles for.
     *
     * @return void
     */
    public function __construct(GeoOverlay $overlay)
    {
        $this->overlay = $overlay;
        $this->tempPath = config('overlay.tiles.tmp_dir')."/{$overlay->id}";
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $disk = config('geo.overlay_storage_disk');
            $this->file = new GenericFile("{$disk}://{$this->overlay->getPathAttribute()}");
            FileCache::getOnce($this->file, [$this, 'generateTiles']);
            $this->uploadToStorage();
            $this->overlay->tilingInProgress = false;
            $this->overlay->save();
        } finally {
            File::deleteDirectory($this->tempPath);
        }
    }

    /**
     * Generate tiles for the image and put them to temporary storage.
     *
     * @param string $path Path to the cached image file.
     */
    public function generateTiles(GenericFile $file, $path)
    {
        $this->getVipsImage($path)->dzsave($this->tempPath, [
            'layout' => 'zoomify',
            'container' => 'fs',
            'strip' => true,
        ]);
    }

    /**
     * Upload the tiles from temporary local storage to the tiles storage disk.
     */
    public function uploadToStorage()
    {
        // +1 for the connecting slash.
        $prefixLength = strlen($this->tempPath) + 1;
        $iterator = $this->getIterator($this->tempPath);
        $disk = Storage::disk(config('geo.overlay_storage_disk'));
        $fragment = $this->overlay->id;
        try {
            foreach ($iterator as $pathname => $fileInfo) {
                echo "pathname: " . $pathname . "<br>";
                echo "fileInfo: " . $fileInfo . "<br>";
                echo "disk path: " . $this->overlay->volume_id . "/" . $fragment . "<br>";
                $disk->putFileAs("{$this->overlay->volume_id}/{$fragment}", $fileInfo, substr($pathname, $prefixLength));
            }
        } catch (Exception $e) {
            $disk->deleteDirectory($fragment);
            throw $e;
        }
    }

    /**
     * Get the vips image instance.
     *
     * @param string $path
     *
     * @return \Jcupitt\Vips\Image
     */
    protected function getVipsImage($path)
    {
        return VipsImage::newFromFile($path, ['access' => 'sequential']);
    }

    /**
     * Get the recursive directory iterator for the given path.
     *
     * @param string $path
     *
     * @return RecursiveIteratorIterator
     */
    protected function getIterator($path)
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                FilesystemIterator::KEY_AS_PATHNAME |
                    FilesystemIterator::CURRENT_AS_FILEINFO |
                    FilesystemIterator::SKIP_DOTS
            )
        );
    }
}
