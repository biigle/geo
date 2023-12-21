<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Volume;
use DivisionByZeroError;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPExif\Reader\Reader;
use PHPExif\Enum\ReaderType;
use Illuminate\Validation\ValidationException;

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
     * @param StorePlainGeoOverlay $request
     * @param int $id Volume ID
     */
    public function storeGeoTiff(Request $request)
    {
        // validation logic
        $validated = $request->validate([
            'geotiff' => 'required|file|mimetypes:image/tiff',
        ]);

        // return DB::transaction(function () use ($request) {
            $file = $request->file('geotiff');
            // reader with Exiftool adapter
            $reader = Reader::factory(ReaderType::EXIFTOOL);
            $exif = $reader->read($file)->getRawData();


            //  1 = 'pixelIsArea', 2 = 'pixelIsPoint', 32767 = 'user-defined'
            $rasterType = $exif['GeoTiff:GTRasterType'];
            //find out which coord-system we're dealing with
            $modelTypeKey = $exif['GeoTiff:GTModelType'];
            switch($modelTypeKey) {
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

            $width = $exif['IFD0:ImageWidth'];
            $height = $exif['IFD0:ImageHeight'];
            // modelTiePointTag = (I,J,K,X,Y,Z)
            $modelTiePoints = array_map('floatval', explode(" ", $exif['IFD0:ModelTiePoint']));
            // for top-left corner, extract the ModelTiePoint Coordinates (in raster space)
            $top_left = [$modelTiePoints[0], $modelTiePoints[1]];
            $bottom_left = [$top_left[0], $height];
            $top_right = [$width, $top_left[1]];
            $bottom_right = [$width, $height];
            // define the corners
            $corners = [$top_left, $bottom_left, $top_right, $bottom_right];

            // Convert corners from raster-space to model-space
            // see https://github.com/opengeospatial/geotiff/blob/master/GeoTIFF_Standard/standard/annex-b.adoc#coordinate-
            if(array_key_exists('IFD0:PixelScale', $exif)) {
                // ModelPixelScale = (Sx, Sy, Sz)
                $pixelScale = array_map('floatval', explode(" ", $exif['IFD0:PixelScale']));
                // if PixelScale is ill-defined and ModelTransformation is not given -> throw error
                if(($pixelScale[0] === 0 || $pixelScale[1] === 0) && !array_key_exists('IFD0:ModelTransformation', $exif)) {
                    throw ValidationException::withMessages(
                        [
                            'affineTransformation' => ['The GeoTIFF file does not have an affine transformation.'],
                        ]
                    );
                }

                try {
                    // Tx = X - I/Sx
                    $Tx = $modelTiePoints[3] - ($modelTiePoints[0] / $pixelScale[0]);
                     // Ty = Y + J/Sy
                    $Ty = $modelTiePoints[4] - ($modelTiePoints[1] / $pixelScale[1]);
                } catch(DivisionByZeroError $e) {
                    throw $e;
                }
                // Tz = Z - K/Sz (if not 0)
                $Tz = $pixelScale[2] === 0 ? 0 : ($modelTiePoints[5] - ($modelTiePoints[2] / $pixelScale[1]));
                $projected = $this->rasterToModelTransform($corners, $pixelScale[0], $pixelScale[1], $Tx, $Ty, $Tz);

            } elseif(array_key_exists('IFD0:ModelTransformation', $exif)) {
                // another way of transforming
            } else {
                // if PixelScale is ill-defined and ModelTransformation is not given -> throw error
                throw ValidationException::withMessages(
                    [
                        'affineTransformation' => ['The GeoTIFF file does not have an affine transformation.'],
                    ]
                );
            }


            // determine the projected coordinate system in use
            if($modelType === 'projected') {
                // project to correct CRS (WGS84)
                if($exif['GeoTiff:ProjectedCSType']) {
                    echo 'PCS: ' . $exif['GeoTiff:ProjectedCSType'] . '<br>';
                    //TODO: check if projection is already WGS 84
                    // else --> use proj4 and transform to correct one
                }
            } else {
                throw ValidationException::withMessages(
                    [
                        'modelType' => ["The GeoTIFF coordinate-system of type '{$modelType}' is not supported. Use a 'projected' coordinate-system instead!"],
                    ]
                );
            }


            echo 'ModelType: ' . $modelType . '<br>';
            echo 'corners: ' . json_encode($corners) . '<br>';
            echo 'projected: ' . json_encode($projected) . '<br>';

            dd($exif);
            // $overlay = new GeoOverlay;
            // $overlay->volume_id = $request->volume->id;
            // $overlay->name = $request->input('name', $file->getClientOriginalName());
            // $overlay->top_left_lat = $request->input('top_left_lat');
            // $overlay->top_left_lng = $request->input('top_left_lng');
            // $overlay->bottom_right_lat = $request->input('bottom_right_lat');
            // $overlay->bottom_right_lng = $request->input('bottom_right_lng');
            // $overlay->save();
            // $overlay->storeFile($file);

            // return $overlay;
        // });
    }

    /**
     * Transform a raster-coordinate into a model-space coordinate.
     *
     * @param $modelTiePoints
     * @param $pixelScale
     *
     * @return boolean
     */
    protected function rasterToModelTransform($corners, $Sx, $Sy, $Tx, $Ty, $Tz)
    {
        $projected = [];
        // transformation matrix for relationship between raster and model space
        // | Sx * I + Tx |
        // | Sy * J + Ty |
        // | Sz * k + Tz |
        foreach($corners as $c) {
            $projected_c = [
                ($Sx * $c[0]) + $Tx,
                ($Sy * $c[1]) + $Ty,
            ];
            // push to result-array
            $projected[] = $projected_c;
        }
        return $projected;
    }
}

