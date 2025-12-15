<?php

namespace Biigle\Modules\Geo\Jobs;

use File;
use FileCache;
use Biigle\FileCache\GenericFile;
use Biigle\Jobs\TileSingleObject;
use Biigle\Modules\Geo\GeoOverlay;
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

    protected $vipsImage;

    protected $exif;

    protected $noDataValue;

    /**
     * Create a new job instance.
     *
     * @param GeoOverlay $file The Overlay to generate tiles for.
     *
     * @return void
     */    
    public function __construct(GeoOverlay $file, array $exif)
    {
        parent::__construct(config('geo.tiles.overlay_storage_disk'), "{$file->id}/{$file->id}_tiles");
        $this->file = $file;
        $this->tempPath = config('geo.tiles.tmp_dir')."/{$file->id}";
        $this->exif = $exif;
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
        $this->vipsImage = $this->getVipsImage($path);
        $this->setNoDataValue();

        $min = $this->getMin();
        $max = $this->vipsImage->max();

        // Must be called before imageNormalization since pixel values could be changed
        $alpha = $this->generateAlphaMask();

        // If smaller or larger range is used, normalize pixel range to enhance contrast
        if ($min != 0 && $max != 255) {
            $this->vipsImage = $this->imageNormalization($min, $max);
        }

        $this->vipsImage = $this->maybeAddAlpha($alpha);

        $this->vipsImage->dzsave($this->tempPath, [
            'layout' => 'zoomify',
            'container' => 'fs',
            'strip' => true,
            'suffix' => '.png',
            'background' => [255, 255, 255, 0],
        ]);
    }

    /**
     *
     * Get the image's minimum by ignoring the no data values if they are present
     *
     * @return float
     */
    protected function getMin()
    {
        $min = $this->vipsImage->min();

        if (is_null($this->noDataValue)) {
            return $min;
        }

        if ($this->minimumEqualsNoDataValue() || $this->noDataValue < $min) {
            // Exclude no data values since they do not represent the minimum
            $min = $this->vipsImage
                ->more($this->noDataValue)
                ->ifthenelse($this->vipsImage, 9999, ['blend' => false])
                ->min();
        }

        return $min;
    }

    /**
     * Set the no data value
     *
     * @return void
     */
    protected function setNoDataValue()
    {
        $this->noDataValue = array_key_exists('IFD0:GDALNoData', $this->exif) ? $this->exif['IFD0:GDALNoData'] : null;

        if (is_null($this->noDataValue)) {
            return;
        }

        // If image's minimum equals the no data value,
        // return the minimum because otherwise comparisons with the no data value will not work
        $this->noDataValue = $this->minimumEqualsNoDataValue() ? $this->vipsImage->min() : $this->noDataValue;
    }

    /**
     * Checks if image's min and no data value is (almost) equal
     *
     * @return bool
     */
    protected function minimumEqualsNoDataValue()
    {
        if (is_null($this->noDataValue)) {
            return false;
        }

        $min = $this->vipsImage->min();
        return $min != 0 && $this->noDataValue / $min >= 0.999 || $min === $this->noDataValue;
    }

    /**
     * Generates an alpha mask for images containing the no data value.
     * Otherwise, return null.
     *
     * @return VipsImage|null
     */
    protected function generateAlphaMask()
    {
        if (is_null($this->noDataValue)) {
            return;
        }

        $hasMissingValues = $this->vipsImage->hist_find()->writeToArray()[$this->noDataValue] > 0;
        // Create alpha mask if image contains noData values
        return $hasMissingValues ?
            $this->vipsImage->equal($this->noDataValue)->ifthenelse(0, 255, ['blend' => false]) :
            null;
    }

    /**
     * Adds the alpha mask to an image if the image contains no data values
     *
     * @param mixed $alpha
     *
     * @return VipsImage
     */
    protected function maybeAddAlpha($alpha)
    {
        if (is_null($alpha)) {
            return $this->vipsImage;
        }

        $image = null;
        // Set transparency to 0 if pixel is a no data value
        if ($this->vipsImage->bands === 4) {
            // replace old alpha channel in RGBA image
            $this->vipsImage->extract_band(0, ['n' => 3]);
            $image = $this->vipsImage->bandjoin($alpha);
        } else {
            $image = $this->vipsImage->bandjoin($alpha);
        }

        return $image;
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
     * Normalize the image band to 0 to 255 to enhance contrast
     * since some images use only a very small pixel range.
     *
     * @param float $min minimum value of color-level of the input image
     * @param float $max maximum value of color-level of the input image
     * 
     * @return \Jcupitt\Vips\Image
     */
    public function imageNormalization($min, $max)
    {
        // band intensity normalization x' = (x - $min) / ($max - $min) * 255
        return $this->vipsImage
            ->subtract($min)
            ->multiply(255 / ($max - $min))
            ->cast('uchar');
    }
}
