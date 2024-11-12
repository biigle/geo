<?php

namespace Biigle\Modules\Geo\Jobs;

use Biigle\FileCache\GenericFile;
use Biigle\Jobs\TileSingleObject;
use FileCache;
use Biigle\Modules\Geo\GeoOverlay;
use File;
use Jcupitt\Vips\Image as VipsImage;

class TileSingleOverlay extends TileSingleObject
{

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
        parent::__construct($storage, $targetPath);
        $this->file = $file;
        $this->tempPath = config('geo.tiles.tmp_dir')."/{$file->id}";
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
            $this->file->save();
        } finally {
            File::deleteDirectory($this->tempPath);
        }
    }

     /**
     * Generate tiles for the object and put them to temporary storage.
     *
     * @param File $file
     * @param string $path Path to the cached image file.
     */
    public function generateTiles($file, $path)
    {
        $vipsImage = $this->getVipsImage($path);
        // exclude the NoData values (-99999) of the geoTIFF file when searching the min
        $min = $vipsImage->equal(-99999)->ifthenelse(0, $vipsImage)->min();
        $max = $vipsImage->max();

        if($min < 0 || $min > 255 || $max < 0 || $max > 255) {
            $this->imageNormalization($vipsImage, $min, $max)->dzsave($this->tempPath, [
                'layout' => 'zoomify',
                'container' => 'fs',
                'strip' => true,
            ]);
        } else {
            parent::generateTiles($file, $path);
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
        return VipsImage::newFromFile($path);
    }

    /**
     * Normalize the image band to 0 to 255
     * 
     * @param \Jcupitt\Vips\Image $vipsImage
     * @param float $min minimum value of color-level of the input image
     * @param float $max maximum value of color-level of the input image
     * 
     * @return \Jcupitt\Vips\Image
     */
    protected function imageNormalization($vipsImage, $min, $max)
    {
        // band intensity normalization x' = (x - $min) / ($max - $min) * 255
        return $vipsImage->subtract($min)->divide($max - $min)->multiply(255);
    }
}
