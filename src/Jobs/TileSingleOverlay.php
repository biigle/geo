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

        // exclude the NoData values (-99999) of the geoTIFF file when searching the min
        $min = $vipsImage->min();
        // Check if metadata returns shortened missing value
        $minIsMissingValue = $min != 0 && $missingValue / $min >= 0.999;
        $missingValue = $minIsMissingValue ? $min : $missingValue;

        if ($minIsMissingValue || $missingValue <= $min) {
            $min = $vipsImage
                ->more($missingValue)
                ->ifthenelse($vipsImage, 9999, ['blend' => false])
                ->min();
        }
        $max = $vipsImage->max();

        $newImage = $vipsImage;
        if ($min != 0 && $max != 255) {
            $newImage = $this->imageNormalization($vipsImage, $min, $max);
        }

        $hasMissingValues = $vipsImage->hist_find()->writeToArray()[$missingValue] > 0;
        if ($hasMissingValues) {
            $a1 = $vipsImage->less($missingValue)->ifthenelse(255, 0);
            $a2 = $vipsImage->more($missingValue)->ifthenelse(255, 0);

            if ($newImage->bands >= 3) {
                $a1 = $a1->bandand();
                $a2 = $a2->bandand();
            }

            $alpha = $a1->orimage($a2);

            if ($newImage->bands === 4) {
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
     * Normalize the image band to 0 to 255
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
