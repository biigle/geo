<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Jobs\TileSingleOverlay;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Modules\Geo\Http\Requests\StoreGeotiffOverlay;
use Biigle\Modules\Geo\Services\Support\GeoManager;
use Biigle\Volume;
use Illuminate\Http\Response;
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
     * @param StoreGeotiffOverlay $request
     * @param int $id Volume ID
     */
    public function storeGeoTiff(StoreGeotiffOverlay $request)
    {

        // return DB::transaction(function () use ($request) {
        $file = $request->file('geotiff');
        $fileName = $request->input('name', $file->getClientOriginalName());
        // create GeoManager-class from uploadedFile
        $geotiff = new GeoManager($file);
        $volumeId = $request->volumeId;

        // check whether file exists alread in DB 
        $existingFileNames = GeoOverlay::where('volume_id', $volumeId)->pluck('name')->toArray();
        if(in_array($fileName, $existingFileNames)) {
            // strip the name if too long
            $fileNameShort = strlen($fileName) > 25 ? substr($fileName, 0, 25) . "..." : $fileName;
            throw ValidationException::withMessages(
                [
                    'fileExists' => ["The geoTIFF \"{$fileNameShort}\" has already been uploaded."],
                ]
            );
        }

        // find out which coordinate-system we're dealing with
        $modelType = $geotiff->getCoordSystemType();
        // Retreive the four corner coordinates of the geoTIFF in raster space
        $corners = $geotiff->getCorners();
        // Convert corners from RASTER-SPACE to MODEL-SPACE
        $minMaxCoords = $geotiff->convertToModelSpace($corners);

        // Change MODEL SPACE to WGS 84
        // determine the projected coordinate system in use
        if ($modelType === 'projected') {
            // get the ProjectedCSTypeTag from the geoTIFF (if exists)
            $pcsCode = is_null($geotiff->getKey('GeoTiff:ProjectedCSType')) ? null : intval($geotiff->getKey('GeoTiff:ProjectedCSType'));
            if (!is_null($pcsCode)) {
                // project to correct CRS (WGS84)
                switch ($pcsCode) {
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
                        $overlay = $this->saveGeoOverlay($volumeId, $fileName, $minMaxCoords, $file);
                        break;
                    default:
                        // use proj4-functions to transform to WGS 84
                        $minMaxCoordsWGS = $geotiff->transformModelSpace($minMaxCoords, "EPSG:{$pcsCode}");
                        // save data in GeoOverlay DB
                        $overlay = $this->saveGeoOverlay($volumeId, $fileName, $minMaxCoordsWGS, $file);
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
        $overlay->top_left_lng = number_format($coords[0][0], 13);
        $overlay->top_left_lat = number_format($coords[0][1], 13);
        $overlay->bottom_right_lng = number_format($coords[1][0], 13);
        $overlay->bottom_right_lat = number_format($coords[1][1], 13);
        $overlay->save();
        $overlay->storeFile($file);
        $this->submitTileJob($overlay);

        // echo 'name: ' . $fileName . "<br>";
        // echo 'top_left_lng: ' . json_encode(number_format($coords[0][0], 15)) . '<br>';
        // echo 'top_left_lat: ' . json_encode(number_format($coords[0][1], 15)) . '<br>';
        // echo 'bottom_right_lng: ' . json_encode(number_format($coords[1][0], 15)) . '<br>';
        // echo 'bottom_right_lat: ' . json_encode(number_format($coords[1][1], 15)) . '<br>';
        return $overlay;
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
