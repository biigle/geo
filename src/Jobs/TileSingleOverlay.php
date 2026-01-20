<?php

namespace Biigle\Modules\Geo\Jobs;

use File;
use FileCache;
use Throwable;
use Biigle\User;
use Biigle\FileCache\GenericFile;
use Biigle\Jobs\TileSingleObject;
use Biigle\Modules\Geo\GeoOverlay;
use Jcupitt\Vips\Image as VipsImage;
use Biigle\Modules\Geo\Events\GeoTiffUploadFailed;
use Biigle\Modules\Geo\Events\GeoTiffUploadSucceeded;

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
     * Image to process
     *
     * @var VipsImage
     */
    protected $vipsImage;

    /**
     * Image's exif data
     *
     * @var array
     */
    protected $exif;

    /**
     * Value representing missing data
     *
     * @var int|float
     */
    protected $noDataValue;

    /**
     * User of the request
     *
     * @var User
     */
    protected $user;

    /**
     * Create a new job instance.
     *
     * @param GeoOverlay $file The Overlay to generate tiles for.
     *
     * @return void
     */    
    public function __construct(GeoOverlay $file, User $user, array $exif)
    {
        parent::__construct(config('geo.tiles.overlay_storage_disk'), "{$file->id}/{$file->id}_tiles");
        $this->file = $file;
        $this->tempPath = config('geo.tiles.tmp_dir')."/{$file->id}";
        $this->exif = $exif;
        $this->user = $user;
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

        $this->validateImage();
        $this->setNoDataValue();

        $min = $this->getMin();
        $max = $this->vipsImage->max();
        $isBlackImg = (int) $min === 0 && (int) $max === 0;

        if (!$isBlackImg && $this->vipsImage->bands === 1) {
            // Must be called before imageNormalization since pixel values could be changed
            $alpha = $this->generateAlphaMask();

            // If smaller or larger range is used, normalize pixel range to enhance contrast
            if ($min != 0 || $max != 255) {
                $this->vipsImage = $this->imageNormalization($min, $max);
            }

            $this->vipsImage = $this->maybeAddAlpha($alpha);

            // Casted images can cause segmentation faults when using dzsave.
            // Recreate images to apply type cast completely before tiling.
            if ($this->vipsImage->format != 'uchar') {
                $this->vipsImage = $this->vipsImage->cast('uchar');
                $w = $this->vipsImage->width;
                $h = $this->vipsImage->height;
                $f = $this->vipsImage->format;
                $b = $this->vipsImage->bands;
                $this->vipsImage = $this->vipsImage->writeToMemory();
                $this->vipsImage = VipsImage::newFromMemory($this->vipsImage, $w, $h, $b, $f);
            }
        }

        $this->vipsImage->dzsave($this->tempPath, [
            'layout' => 'zoomify',
            'container' => 'fs',
            'strip' => true,
            'suffix' => '.png',
            'background' => [255, 255, 255, 0],
        ]);

        GeoTiffUploadSucceeded::dispatch($this->file, $this->user);
    }

    /**
     * Check whether image color channel count is valid.
     * If more than 4 channels are given, fail this job and dispatch a fail event.
     *
     * @return void
     */
    protected function validateImage()
    {
        // Fail job if image doesn't use BW, Grayscale or RGB(A) color space
        if ($this->vipsImage->bands > 4) {
            $file = $this->file->name;
            $ccount = $this->vipsImage->bands;
            $msg = "Upload of '$file' failed. Image can have at most 4 color channels, but $ccount channels are given.";
            $this->failAndNotify($msg);
        }
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
        return $min != 0 && $this->noDataValue / $min >= 0.999 || intval($min) === intval($this->noDataValue);
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

        // alpha mask is a 'uchar' matrix which must have the same format as the image
        $alpha = $alpha->cast($this->vipsImage->format);
        // Set transparency to 0 if pixel is a no data value
        return $this->vipsImage->bandjoin($alpha);
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
    protected function imageNormalization($min, $max)
    {
        // band intensity normalization x' = (x - $min) / ($max - $min) * 255
        return $this->vipsImage
            ->subtract($min)
            ->multiply(255 / ($max - $min))
            ->rint();
    }

    /**
     * Delete overlay, dispatch fail event and fail job.
     *
     * @param mixed $msg Error message
     *
     * @return void
     */
    protected function failAndNotify($msg)
    {
        if (GeoOverlay::find($this->file->id)->exists()) {
            $this->file->delete();
        }
        GeoTiffUploadFailed::dispatch($this->user, $msg);
        $this->fail();
    }

    /**
     * Dispatch fail event after job failed
     *
     * @param mixed $exception
     *
     * @return void
     */
    public function failed(?Throwable $exception)
    {
        $file = $this->file->name;
        $this->failAndNotify("Upload of '$file' failed. Please try again.");
    }
}
