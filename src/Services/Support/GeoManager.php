<?php

namespace Biigle\Modules\Geo\Services\Support;

use DivisionByZeroError;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PHPExif\Enum\ReaderType;
use PHPExif\Reader\Reader;
use proj4php\Point;
use proj4php\Proj;
use proj4php\Proj4php;

class GeoManager
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
     * Create a new GeoOverlay instance.
     *
     * @param UploadedFile $file The uploaded geotiff.
     *
     * @return void
     */
    public function __construct(UploadedFile $file)
    {
        // reader with Exiftool adapter
        $reader = Reader::factory(ReaderType::EXIFTOOL);
        $this->exif = $reader->read($file)->getRawData();
    }

    /**
     * Return the geoKey if it exists in the geoTIFF metadata
     * 
     * @return null/object at $geoKey from exif-array
     */
    public function getKey($geoKey)
    {
        if(array_key_exists($geoKey, $this->exif)) {
            return $this->exif[$geoKey];
        }
        return null;
    }

    /**
     * Retreive the type of coordinate reference system used in the geoTIFF.  
     * 
     * @return string the coordinate system type
     */
    public function getCoordSystemType()
    {
        if (array_key_exists('GeoTiff:GTModelType', $this->exif)) {
            $modelTypeKey = $this->exif['GeoTiff:GTModelType'];
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
        } else {
            throw ValidationException::withMessages(
                [
                    'missingModelType' => ['The geoTIFF file does not have the required GTModelTypeTag.'],
                ]
            );
        }

        return $modelType;
    }

    /**
     * Retreive the four corner coordinates of the geoTIFF in raster space
     * 
     * @return Array the outer coordinates [$top_left, $bottom_left, $top_right, $bottom_right]
     */
    public function getCorners()
    {
        $width = $this->exif['IFD0:ImageWidth'];
        $height = $this->exif['IFD0:ImageHeight'];

        // modelTiePointTag = (I,J,K,X,Y,Z)
        if (array_key_exists('IFD0:ModelTiePoint', $this->exif)) {
            $modelTiePoints = array_map('floatval', explode(" ", $this->exif['IFD0:ModelTiePoint']));
            $tie_point_keys = ['I', 'J', 'K', 'X', 'Y', 'Z'];
            $tie_points_combined = array_combine($tie_point_keys, $modelTiePoints);
            // make variables availabe
            extract($tie_points_combined);
            // set class variable values
            $this->I = $I;
            $this->J = $J;
            $this->X = $X;
            $this->Y = $Y;
        } else {
            throw ValidationException::withMessages(
                [
                    'missingModelTiePoints' => ['The geoTIFF file does not have the required ModelTiePointTag.'],
                ]
            );
        }

        // for top-left corner, extract the ModelTiePoint Coordinates (in raster space: I,J)
        $top_left = [$I, $J];
        $bottom_left = [$I, $height];
        $top_right = [$width, $J];
        $bottom_right = [$width, $height];
        // return the corners
        return [$top_left, $bottom_left, $top_right, $bottom_right];
    }

    /**
     * Convert corners from RASTER-SPACE to MODEL-SPACE
     * 
     * @return
     */
    public function convertToModelSpace($corners) 
    {
        // see https://github.com/opengeospatial/geotiff/blob/master/GeoTIFF_Standard/standard/annex-b.adoc#coordinate-
        if (array_key_exists('IFD0:PixelScale', $this->exif)) {
            // PixelScale = (Sx, Sy, Sz)
            $pixelScale = array_map('floatval', explode(" ", $this->exif['IFD0:PixelScale']));
            // if PixelScale is ill-defined and ModelTransformation is not given -> throw error
            if (($pixelScale[0] === 0 || $pixelScale[1] === 0) && !array_key_exists('IFD0:ModelTransformation', $this->exif)) {
                throw ValidationException::withMessages(
                    [
                        'affineTransformation' => ['The geoTIFF file does not have an affine transformation.'],
                    ]
                );
            }

            try {
                // Tx = X - I/Sx
                $Tx = $this->X - ($this->I / $pixelScale[0]);
                // Ty = Y + J/Sy
                $Ty = $this->Y - ($this->J / $pixelScale[1]);
                // Tz = Z - K/Sz (if not 0; aka. the 2D-case)
                // $Tz = $pixelScale[2] === 0 ? 0 : ($Z - ($K / $pixelScale[1]));
            } catch (DivisionByZeroError $e) {
                throw $e;
            }
            // transformation matrix for relationship between raster and model space
            // | Sx * I + Tx |
            // | Sy * J + Ty |
            // | Sz * k + Tz |
            foreach ($corners as $corner) {
                $projected[] = [
                    ($pixelScale[0] * $corner[0]) + $Tx,
                    - ($pixelScale[1] * $corner[1]) + $Ty,
                ];
            }
            // get the minimum and maximum coordinates of the geoTIFF
            $min_max_coords = $this->getMinMaxCoordinate($projected);
        } elseif (array_key_exists('IFD0:ModelTransformation', $this->exif)) {
            // TODO: Test with geoTIFF (could not find a testcase yet)!!
            // another way of transforming raster- to model-space with ModelTransformationTag (only 2D case implemented)
            $model_transform = $this->exif['IFD0:ModelTransformation'];
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
            // if PixelScale is ill-defined and ModelTransformation is not given -> throw error
            throw ValidationException::withMessages(
                [
                    'affineTransformation' => ['The geoTIFF file does not have an affine transformation.'],
                ]
            );
        }

        return $min_max_coords;
    }


    /**
     * Transform coordinates from one model-space into another 
     *
     * @param $coords_current min and max coordinates of the geoTIFF
     * @param $pcs_code from the ProjectedCSTypeTag of the geoTIFF
     *
     * @return array in form [min_x, min_y, max_x, max_y]
     */
    public function transformModelSpace($coords_current, $pcs_code)
    {
        // Initialise Proj4
        $proj4 = new Proj4php();
        // create the WGS84 projection
        $projWGS84 = new Proj('EPSG:4326', $proj4);
        // create projection of current geoTIFF from ProjectedCSTypeTag
        try {
            $proj_current = new Proj($pcs_code, $proj4);
        } catch (Exception $e) {
            report($e);
            throw ValidationException::withMessages(
                [
                    'transformError' => ['An error occurred during transformation of the projected coordinate system to WGS84: ' . $e->getMessage()],
                ]
            );
        }
        $transformed_coords = [];

        for ($i = 0; $i < count($coords_current); $i += 2) {
            // create a point
            $pointSrc = new Point($coords_current[$i], $coords_current[$i + 1], $proj_current);
            // transform the point between datums
            $projected_point = $proj4->transform($projWGS84, $pointSrc)->toArray();
            $transformed_coords[] = [$projected_point[0], $projected_point[1]];
        };

        return $transformed_coords;
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
        $x_coordinates = array_map(fn ($pt) => $pt[0], $projected);
        $y_coordinates = array_map(fn ($pt) => $pt[1], $projected);

        return [
            min($x_coordinates),
            min($y_coordinates),
            max($x_coordinates),
            max($y_coordinates)
        ];
    }

}