<?php

namespace Biigle\Modules\Geo\Jobs;

use File;
use FileCache;
use PHPExif\Reader\Reader;
use PHPExif\Enum\ReaderType;
use Biigle\FileCache\GenericFile;
use Biigle\Jobs\TileSingleObject;
use Biigle\Modules\Geo\GeoOverlay;
use Jcupitt\Vips\Image as VipsImage;
use Illuminate\Support\Facades\Storage;

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
    public function __construct(GeoOverlay $file)
    {
        parent::__construct(config('geo.tiles.overlay_storage_disk'), "{$file->id}/{$file->id}_tiles");
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
        $missingValue = $this->getExifData($path)['IFD0:GDALNoData'];

        $min = $vipsImage->min();
        // Check if metadata returns truncated no data value
        $minIsMissingValue = $min != 0 && $missingValue / $min >= 0.999;
        // Use no data value from file instead of truncated value from exif data
        $missingValue = $minIsMissingValue ? $min : $missingValue;

        if ($minIsMissingValue || $missingValue <= $min) {
            // Exclude no data values since they do not represent the minimum
            $min = $vipsImage
                ->more($missingValue)
                ->ifthenelse($vipsImage, 9999, ['blend' => false])
                ->min();
        }
        $max = $vipsImage->max();

        $newImage = $vipsImage;
        // If smaller or larger range is used, normalize pixel range to enhance contrast
        if ($min != 0 && $max != 255) {
            $newImage = $this->imageNormalization($vipsImage, $min, $max);
        }

        // Check whether image contains no data values
        $hasMissingValues = $vipsImage->hist_find()->writeToArray()[$missingValue] > 0;
        if ($hasMissingValues) {
            // Create alpha mask
            $alpha = $vipsImage->equal($missingValue)->ifthenelse(0, 255, ['blend' => false]);

            // Set transparecy to 0 if pixel is a no data value
            if ($newImage->bands === 4) {
                // discard old alpha channel
                $newImage = $newImage->extract_band(0, ['n' => 3]);
                $newImage = $newImage->bandjoin($alpha);
            } else {
                $newImage = $newImage->bandjoin($alpha);
            }
        }

        $newImage->dzsave($this->tempPath, [
            'layout' => 'zoomify',
            'container' => 'fs',
            'strip' => true,
            'suffix' => '.png',
            'background' => [255, 255, 255, 0],
        ]);
    }

    protected function getExifData($path)
    {
        $reader = Reader::factory(ReaderType::EXIFTOOL);
        return $reader->read($path)->getRawData();
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
     * @param \Jcupitt\Vips\Image $vipsImage
     * @param float $min minimum value of color-level of the input image
     * @param float $max maximum value of color-level of the input image
     * 
     * @return \Jcupitt\Vips\Image
     */
    public function imageNormalization($vipsImage, $min, $max)
    {
        // band intensity normalization x' = (x - $min) / ($max - $min) * 255
        return $vipsImage
            ->subtract($min)
            ->multiply(255 / ($max - $min))
            ->cast('uchar');
    }
}
