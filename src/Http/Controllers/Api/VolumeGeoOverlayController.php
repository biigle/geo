<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Jobs\TileSingleOverlay;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Modules\Geo\Http\Requests\StoreGeotiffOverlay;
use Biigle\Volume;
use DivisionByZeroError;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use PHPExif\Reader\Reader;
use PHPExif\Enum\ReaderType;
use Illuminate\Validation\ValidationException;
use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;

class VolumeGeoOverlayController extends Controller
{
    /**
     * Shows the geo overlays of the specified volume.
     *
     * @api {get} volumes/:id/geo-overlays Get geo overlays
     * @apiGroup Geo
     * @apiName VolumesumesIndexGeoOverlays
     * @apiPermission projectMember
     *
     * @apiParam {Number} id The volume ID.
     * @apiSuccessExample {json} Success response:
     * [
     *     {
     *         "id": 1,
     *         "name": "My geo overlay",
     *         "top_left_lat": 6.7890,
     *         "top_left_lng": 1.2345,
     *         "bottom_right_lat": 7.7890,
     *         "bottom_right_lng": 2.2345,
     *     }
     * ]
     *
     * @param int $id volume id
     */
    public function index($id)
    {
        $volume = Volume::findOrFail($id);
        $this->authorize('access', $volume);

        if ($volume->isVideoVolume()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return GeoOverlay::where('volume_id', $id)->get();
    }

    /**
     * TODO: Change API-Documentation
     * Stores a new geo overlay that was uploaded with the plain method.
     *
     * @api {post} volumes/:id/geo-overlays/plain Upload a plain geo overlay
     * @apiGroup Geo
     * @apiName VolumesumesStoreGeoOverlaysPlain
     * @apiPermission projectAdmin
     *
     * @apiParam {Number} id The volume ID.
     * @apiParam (Required attributes) {File} file The image file of the geo overlay. Allowed file formats ate JPEG, PNG and TIFF. The file must not be larger than 10 MByte.
     * @apiParam (Required attributes) {Number} top_left_lat Latitude of the top left corner of the image file in WGS 84 (EPSG:4326).
     * @apiParam (Required attributes) {Number} top_left_lng Longitude of the top left corner of the image file in WGS 84 (EPSG:4326).
     * @apiParam (Required attributes) {Number} bottom_right_lat Latitude of the bottom right corner of the image file in WGS 84 (EPSG:4326).
     * @apiParam (Required attributes) {Number} bottom_right_lng Longitude of the bottom right corner of the image file in WGS 84 (EPSG:4326).
     *
     * @apiParam (Optional attributes) {String} name A short description of the geo overlay. If empty, the filename will be taken.
     *
     * @apiParamExample {String} Request example:
     * file: bath_map_1.jpg
     * top_left_lat: 52.03737667
     * top_left_lng: 8.49285457
     * bottom_right_lat: 52.03719188
     * bottom_right_lng: 8.4931067
     *
     * @apiSuccessExample {json} Success response:
     * {
     *     "id": 1,
     *     "name": "bath_map_1.jpg",
     *     "volume_id": 123,
     *     "top_left_lat": 52.03737667,
     *     "top_left_lng": 8.49285457,
     *     "bottom_right_lat": 52.03719188,
     *     "bottom_right_lng": 8.4931067,
     * }
     *
     * @param StoreGeotiffOverlay $request
     * @param int $id Volume ID
     */
    public function storeGeoTiff(StoreGeotiffOverlay $request)
    {

        // return DB::transaction(function () use ($request) {
        $file = $request->file('geotiff');
        $file_name = $request->input('name', $file->getClientOriginalName());
        // reader with Exiftool adapter
        $reader = Reader::factory(ReaderType::EXIFTOOL);
        $exif = $reader->read($file)->getRawData();
        $volumeId = $request->volumeId;

        // check whether file exists alread in DB 
        $existing_filenames = GeoOverlay::where('volume_id', $volumeId)->pluck('name')->toArray();
        if(in_array($file_name, $existing_filenames)) {
            // strip the name if too long
            $file_name_short = strlen($file_name) > 25 ? substr($file_name, 0, 25) . "..." : $file_name;
            throw ValidationException::withMessages(
                [
                    'fileExists' => ["The geoTIFF \"{$file_name_short}\" has already been uploaded."],
                ]
            );
        }

        //  1 = 'pixelIsArea', 2 = 'pixelIsPoint', 32767 = 'user-defined'
        // $rasterType = $exif['GeoTiff:GTRasterType'];
        //find out which coord-system we're dealing with
        if (array_key_exists('GeoTiff:GTModelType', $exif)) {
            $modelTypeKey = $exif['GeoTiff:GTModelType'];
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

        $width = $exif['IFD0:ImageWidth'];
        $height = $exif['IFD0:ImageHeight'];

        // modelTiePointTag = (I,J,K,X,Y,Z)
        if (array_key_exists('IFD0:ModelTiePoint', $exif)) {
            $modelTiePoints = array_map('floatval', explode(" ", $exif['IFD0:ModelTiePoint']));
            $tie_point_keys = ['I', 'J', 'K', 'X', 'Y', 'Z'];
            $tie_points_combined = array_combine($tie_point_keys, $modelTiePoints);
            // make variables availabe
            extract($tie_points_combined);
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
        // define the corners
        $corners = [$top_left, $bottom_left, $top_right, $bottom_right];

        // Convert corners from RASTER-SPACE to MODEL-SPACE
        // see https://github.com/opengeospatial/geotiff/blob/master/GeoTIFF_Standard/standard/annex-b.adoc#coordinate-
        if (array_key_exists('IFD0:PixelScale', $exif)) {
            // PixelScale = (Sx, Sy, Sz)
            $pixelScale = array_map('floatval', explode(" ", $exif['IFD0:PixelScale']));
            // if PixelScale is ill-defined and ModelTransformation is not given -> throw error
            if (($pixelScale[0] === 0 || $pixelScale[1] === 0) && !array_key_exists('IFD0:ModelTransformation', $exif)) {
                throw ValidationException::withMessages(
                    [
                        'affineTransformation' => ['The geoTIFF file does not have an affine transformation.'],
                    ]
                );
            }

            try {
                // Tx = X - I/Sx
                $Tx = $X - ($I / $pixelScale[0]);
                // Ty = Y + J/Sy
                $Ty = $Y - ($J / $pixelScale[1]);
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
        } elseif (array_key_exists('IFD0:ModelTransformation', $exif)) {
            // TODO: Test with geoTIFF (could not find a testcase yet)!!
            // another way of transforming raster- to model-space with ModelTransformationTag (only 2D case implemented)
            $model_transform = $exif['IFD0:ModelTransformation'];
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


        // Change MODEL SPACE to WGS 84
        // determine the projected coordinate system in use
        if ($modelType === 'projected') {
            // project to correct CRS (WGS84)
            if (array_key_exists('GeoTiff:ProjectedCSType', $exif)) {
                $pcs_code = intval($exif['GeoTiff:ProjectedCSType']);

                switch ($pcs_code) {
                        // undefined code
                    case 0:
                        throw ValidationException::withMessages(
                            [
                                'unDefined' => ['The projected coordinate system (PCS) is undefined. Provide a PCS using EPSG-system instead.'],
                            ]
                        );
                        break;
                        // user-defined code
                    case 32767:
                        // if ProjectedCS-GeoKey is user-defined --> throw error
                        throw ValidationException::withMessages(
                            [
                                'userDefined' => ['User-defined projected coordinate systems (PCS) are not supported. Provide a PCS using EPSG-system instead.'],
                            ]
                        );
                        break;
                        // WGS 84 code
                    case 4326:
                        // save data in GeoOverlay DB when already in WGS84
                        $overlay = $this->saveGeoOverlay($volumeId, $file_name, $min_max_coords, $file);
                        break;
                    default:
                        // use proj4-functions to transform to WGS 84
                        $min_max_coordsWGS = $this->transformModelSpace($min_max_coords, "EPSG:{$pcs_code}");
                        // save data in GeoOverlay DB
                        $overlay = $this->saveGeoOverlay($volumeId, $file_name, $min_max_coordsWGS, $file);
                }
            } else {
                throw ValidationException::withMessages(
                    [
                        'noPCSKEY' => ["Did not detect the 'ProjectedCSType' geokey in geoTIFF metadata. Make sure this key exists for geoTIFF's containing a projected coordinate system."],
                    ]
                );
            }
        } else {
            throw ValidationException::withMessages(
                [
                    'wrongModelType' => ["The GeoTIFF coordinate-system of type '{$modelType}' is not supported. Use a 'projected' coordinate-system instead!"],
                ]
            );
        }


        echo 'wrongModelType: ' . $wrongModelType . '<br>';
        echo 'PCS: ' . $exif['GeoTiff:ProjectedCSType'] . '<br>';
        echo 'corners: ' . json_encode($corners) . '<br>';
        echo 'projected: ' . json_encode($projected) . '<br>';
        echo 'MinMaxCoord: ' . json_encode($min_max_coords) . '<br>';
        if (isset($min_max_coordsWGS)) {
            echo 'WGS84: ' . json_encode($min_max_coordsWGS) . '<br>';
        }

        // dd($exif);
        // $overlay = new GeoOverlay;
        // $overlay->volume_id = $volumeId->id;
        // $overlay->name = $request->input('name', $file->getClientOriginalName());
        // $overlay->top_left_lat = $request->input('top_left_lat');
        // $overlay->top_left_lng = $request->input('top_left_lng');
        // $overlay->bottom_right_lat = $request->input('bottom_right_lat');
        // $overlay->bottom_right_lng = $request->input('bottom_right_lng');
        // $overlay->save();
        // $overlay->storeFile($file);

        return $overlay;
        // });
    }

    /**
     * Save GeoTIFF data in GeoOverlay DB
     *
     * @param $volumeId ID of the current volume
     * @param $fileName of the original input-file
     * @param $coords min and max coordinates in WGS84 format
     * @param $file the geotiff file from request
     *
     * @return GeoOverlay
     */
    protected function saveGeoOverlay($volumeId, $fileName, $coords, $file)
    {
        $overlay = new GeoOverlay;
        $overlay->volume_id = $volumeId;
        $overlay->name = $fileName;
        $overlay->top_left_lng = $coords[0][0];
        $overlay->top_left_lat = $coords[0][1];
        $overlay->bottom_right_lng = $coords[1][0];
        $overlay->bottom_right_lat = $coords[1][1];
        $overlay->save();
        $overlay->storeFile($file);
        $this->submitTileJob($overlay);
        return $overlay;
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

    /**
     * Transform coordinates from one model-space into another 
     *
     * @param $coords_current min and max coordinates of the geoTIFF
     * @param $pcs_code from the ProjectedCSTypeTag of the geoTIFF
     *
     * @return array in form [min_x, min_y, max_x, max_y]
     */
    protected function transformModelSpace($coords_current, $pcs_code)
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
     * Submit a new tile job for any new overlay.
     *
     * @param GeoOverlay $overlay
     */
    protected function submitTileJob(GeoOverlay $overlay)
    {
        $overlay->tiled = true;
        $overlay->tilingInProgress = true;
        // $job = new TileSingleOverlay($overlay);
        // $job->handle();
        TileSingleOverlay::dispatch($overlay);
    }
}
