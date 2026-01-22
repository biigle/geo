<?php

namespace Biigle\Modules\Geo\Services\Support;

use PHPExif\Reader\Reader;
use PHPExif\Enum\ReaderType;
use Illuminate\Http\UploadedFile;
use Biigle\Modules\Geo\Exceptions\ConvertModelSpaceException;

class GeoManager extends Transformer
{
    /**
     * The raw metadata provided by the geoTIFF.
     *
     * @var Array
     */
    public $exif;

    /**
     * The four corner coordinates of the geoTIFF in raster space
     * 
     * @var Array
     */
    protected $corners;

    /**
     * ModelTiePointTag
     * 
     * @var float the point at location I in raster space
     */
    protected $I;

    /**
     * ModelTiePointTag
     * 
     * @var float the point at location J in raster space
     */
    protected $J;

    /**
     * ModelTiePointTag
     * 
     * @var float the point at location X in model space
     */
    protected $X;

    /**
     * ModelTiePointTag
     * 
     * @var float the point at location Y in model space
     */
    protected $Y;

    /**
     * Set exif data of the given file
     *
     * @param UploadedFile $file
     * @return void
     */
    public function useFile(UploadedFile $file)
    {
        $this->exif = $this->getExifData($file);
    }

    /**
     * Returns exif data of a file
     *
     * @param mixed $file to be read
     * @return array containing the exif data
     */
    protected function getExifData($file)
    {
        // reader with Exiftool adapter
        $reader = Reader::factory(ReaderType::EXIFTOOL);
        return $reader->read($file)->getRawData();
    }

    /**
     * Return the geoKey if it exists in the geoTIFF metadata
     * 
     * @return null/object at $geoKey from exif-array
     */
    public function getKey($geoKey)
    {
        if (array_key_exists($geoKey, $this->exif)) {
            return $this->exif[$geoKey];
        }
        return null;
    }

    /**
     * Retrieve the overlay size (width, height) of the geoTIFF file in pixels
     * 
     * @return Array the pixel width and height [$width, $height] 
     */
    public function getPixelSize()
    {
        $width = $this->exif['IFD0:ImageWidth'];
        $height = $this->exif['IFD0:ImageHeight'];
        return [$width, $height];
    }

    /**
     * Retrieve the overlay size (col, row) of the geoTIFF file in raster-size
     * 
     * @return Array the raster column and row count in raster-space 
     */
    public function getRasterSize($corners)
    {
        $pixelScale = array_map('floatval', explode(" ", $this->exif['IFD0:PixelScale']));
        // [$top_left, $bottom_left, $top_right, $bottom_right];
        $raster_rows = abs(($corners[0][1] - $corners[1][1]) / $pixelScale[1]);
        $raster_cols = abs(($corners[2][0] - $corners[0][0]) / $pixelScale[0]);
        return [$raster_cols, $raster_rows];
    }

    /**
     * Retrieve the overlay size (width, height) of the geoTIFF file in model-space
     * 
     * @return Array the model-space width and height [$width, $height] 
     */
    public function getModelSize($coords)
    {
        $width = number_format($coords[2] - $coords[0], 13);
        $height = number_format($coords[3] - $coords[1], 13);
        return [$width, $height];
    }

    /**
     * Retreive the four corner coordinates of the geoTIFF in raster space
     * 
     * @return Array the outer coordinates [$top_left, $bottom_left, $top_right, $bottom_right]
     */
    protected function getCorners()
    {
        [$width, $height] = $this->getPixelSize();

        // for top-left corner, extract the ModelTiePoint Coordinates (in raster space: I,J)
        $top_left = [0, 0];
        $bottom_left = [0, $height];
        $top_right = [$width, 0];
        $bottom_right = [$width, $height];
        // return the corners
        return [$top_left, $bottom_left, $top_right, $bottom_right];
    }

    /**
     * Convert corners from RASTER-SPACE to MODEL-SPACE
     *
     * @param $corners Top left and bottom right corner
     *
     * @throws ConvertModelSpaceException if PixelScale is ill-defined and ModelTransformation is not given
     *
     * @return array containing the transformed coordinates
     */
    protected function convertToModelSpace($corners)
    {
        // see https://github.com/opengeospatial/geotiff/blob/master/GeoTIFF_Standard/standard/annex-b.adoc#coordinate-transformations
        if (array_key_exists('IFD0:PixelScale', $this->exif) && array_key_exists('IFD0:ModelTiePoint', $this->exif)) {
            // PixelScale = (Sx, Sy, Sz)
            $pixelScale = array_map('floatval', explode(" ", $this->exif['IFD0:PixelScale']));
            // modelTiePointTag = (I,J,K,X,Y,Z)
            $modelTiePoints = array_map('floatval', explode(" ", $this->exif['IFD0:ModelTiePoint']));
            $tie_point_keys = ['I', 'J', 'K', 'X', 'Y', 'Z'];
            $tie_points_combined = array_combine($tie_point_keys, $modelTiePoints);
            // make variables availabe ($I, $J, $K, $X, $Y, $Z)
            extract($tie_points_combined);

            // if PixelScale is ill-defined, skip to next section
            if (($pixelScale[0] !== 0 && $pixelScale[1] !== 0)) {
                // modelTiePointTag = (I,J,K,X,Y,Z)
                // Tx = X - I*Sx
                $Tx = $X - ($I * $pixelScale[0]);
                // Ty = Y + J*Sy
                $Ty = $Y + ($J * $pixelScale[1]);
                // Tz = Z - K*Sz (if not 0; aka. the 2D-case)
                // $Tz = $pixelScale[2] === 0 ? 0 : ($Z - ($K * $pixelScale[1]));

                // transformation matrix for relationship between raster and model space
                // | Sx * I + Tx |
                // | Sy * J + Ty |
                // | Sz * k + Tz |
                foreach ($corners as $corner) {
                    $projected[] = [
                        ($pixelScale[0] * $corner[0]) + $Tx,
                        -($pixelScale[1] * $corner[1]) + $Ty,
                    ];
                }
                // get the minimum and maximum coordinates of the geoTIFF
                $min_max_coords = $this->getMinMaxCoordinate($projected);
            }
        } elseif (array_key_exists('IFD0:ModelTransform', $this->exif)) {
            // another way of transforming raster- to model-space with ModelTransformationTag (only 2D case implemented)
            $model_transform = array_map('doubleval', explode(" ", $this->exif['IFD0:ModelTransform']));
            $keys = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
            $var_array = array_combine($keys, array_slice($model_transform, 0, count($keys)));
            // make variables availabe
            extract($var_array);
            $projected = [];
            // transformation matrix for raster- to model-space, multiply with modelTiePoint (I,J,K)
            // | a b 0 d |
            // | e f 0 h |
            // | 0 0 0 0 |
            foreach ($corners as $corner) {
                $projected[] = [
                    (($a * $corner[0]) + ($b * $corner[1]) + $d),
                    (($e * $corner[0]) + ($f * $corner[1]) + $h)
                ];
            }
            // get the minimum and maximum coordinates of the geoTIFF
            $min_max_coords = $this->getMinMaxCoordinate($projected);
        } else {
            throw new ConvertModelSpaceException();
        }

        return $min_max_coords;
    }

    public function getCoords()
    {
        // Convert corners from RASTER-SPACE to MODEL-SPACE
        $epsg = $this->getEpsgCode();
        $corners = $this->getCorners();
        $coords = $this->convertToModelSpace($corners);
        $coords = $this->transformToEPSG4326($coords, "EPSG:{$epsg}");
        return $this->maybeFixCoords($coords);
    }

    /**
     * Return the min and max coordinate in model-space
     *
     * @param $projected coordinates of the geoTIFF corners
     *
     * @return array
     */
    protected function getMinMaxCoordinate($projected)
    {
        $x_coordinates = array_map(fn($pt) => $pt[0], $projected);
        $y_coordinates = array_map(fn($pt) => $pt[1], $projected);

        return [
            min($x_coordinates),
            min($y_coordinates),
            max($x_coordinates),
            max($y_coordinates)
        ];
    }

    /**
     * Return epsg code from geoTIFF
     *
     * @return int|null
     */
    public function getEpsgCode()
    {
        $type = $this->getCoordSystemType();

        if (is_null($type) || !in_array($type, ['projected', 'geographic'])) {
            return null;
        }

        return intval(
            $this->getKey(
                $type === 'projected' ? 'GeoTiff:ProjectedCSType' : 'GeoTiff:GeographicType'
            )
        );
    }

    /**
     * Retreive the type of coordinate reference system used in the geoTIFF.
     *
     * @return string the coordinate system type
     */
    public function getCoordSystemType()
    {
        $modelTypeKey = $this->getKey('GeoTiff:GTModelType');

        switch ($modelTypeKey) {
            case 1:
                $modelType = 'projected';
                break;
            case 2:
                $modelType = 'geographic';
                break;
            case 3:
                $modelType = 'geocentric';
                break;
            case 32767:
                $modelType = 'user-defined';
                break;
            default:
                $modelType = null;
        }

        return $modelType;
    }

}