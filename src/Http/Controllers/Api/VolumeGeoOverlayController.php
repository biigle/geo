<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Volume;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use PHPExif\Reader\Reader;
use PHPExif\Adapter\Exiftool;
use PHPExif\Enum\ReaderType;

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
        
            //TODO: Read the ModelTiePoint Coordinates and project to correct CRS (WGS84)
            // find  "top_left_lat": 52.03737667,
            //  "top_left_lng": 8.49285457,
            //  "bottom_right_lat": 52.03719188,
            //  "bottom_right_lng": 8.4931067,

            // determine the projected coordinate system in use
            if($modelType === 'projected') {
                if($exif['GeoTiff:ProjectedCSType']) {
                    //TODO: check if projection is already WGS 84
                    // else --> use proj4 and transform to correct one
                }
            }
        


            echo 'ModelType: ' . $modelType . PHP_EOL;
            echo 'Latitude: ' . $exif->getLatitude() . PHP_EOL;
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
}
