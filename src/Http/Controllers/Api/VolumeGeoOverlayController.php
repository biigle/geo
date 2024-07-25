<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Jobs\TileSingleOverlay;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Modules\Geo\Http\Requests\StoreGeotiffOverlay;
use Biigle\Modules\Geo\Http\Requests\UpdateGeotiffOverlay;
use Biigle\Modules\Geo\Services\Support\GeoManager;
use Biigle\Volume;
use Illuminate\Http\Request;
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
     *         "browsing_layer": true,
     *         "context_layer": false,
     *     }
     * ]
     *
     * @param int $id volume id
     * @param String $layer_type If specified, retrieves only a subset of the geo-overlays, e.g. 'browsing_layer', 'context_layer' or null
     */
    public function index(Request $request, $id, $layer_type = null)
    {
        // set layer_type if it appears in the query-url, otherwise null 
        $layer_type  = $layer_type ?: $request->layer_type;
        $volume = Volume::findOrFail($id);
        $this->authorize('access', $volume);

        if ($volume->isVideoVolume()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // retrieve subset of the geo-overlays if layer_type is specified
        if($layer_type == 'browsing_layer') {
            return GeoOverlay::where('volume_id', $id)->where('browsing_layer', true)->get();
        } else if($layer_type == 'context_layer') {
            return GeoOverlay::where('volume_id', $id)->where('context_layer', true)->get();
        } else { // return all geoOverlays
            return GeoOverlay::where('volume_id', $id)->get();
        }
    }

    /**
     * Stores a new geo overlay that was uploaded with the geotiff method.
     *
     * @api {post} volumes/:id/geo-overlays/geotiff Upload a geotiff geo overlay
     * @apiGroup Geo
     * @apiName VolumesStoreGeoTiff
     * @apiPermission projectAdmin
     *
     * @apiParam {Number} volumeId The volume ID.
     * @apiParam (Required attributes) {File} geotiff The geotiff file of the geo overlay. Allowed file formats are TIFF. The file must not be larger than 50 GByte.
     * 
     * @apiParamExample {String} Request example:
     * volumeId: 1
     * geotiff: bath_map_1.tif
     *
     * @apiSuccessExample {json} Success response:
     * {
     *   "volume_id" => 1
     *   "name" => "standardEPSG2013.tif"
     *   "top_left_lng" => "-2.9198048485707"
     *   "top_left_lat" => "57.0974838573358"
     *   "bottom_right_lng" => "-2.9170062535908"
     *   "bottom_right_lat" => "57.0984589659731"
     *   "id" => 3
     *   "tiled" => true
     *   "tilingInProgress" => true
     * }
     *
     * @param StoreGeotiffOverlay $request
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
        if (in_array($fileName, $existingFileNames)) {
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
        // find the width and height of geotiff file in pixels
        $pixelDimensions = $geotiff->getPixelSize();
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
                        $overlay = $this->saveGeoOverlay($volumeId, $fileName, $minMaxCoords, $file, $pixelDimensions);
                        break;
                    default:
                        // use proj4-functions to transform to WGS 84
                        $minMaxCoordsWGS = $geotiff->transformModelSpace($minMaxCoords, "EPSG:{$pcsCode}");
                        // save data in GeoOverlay DB
                        $overlay = $this->saveGeoOverlay($volumeId, $fileName, $minMaxCoordsWGS, $file, $pixelDimensions);
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
    protected function saveGeoOverlay($volumeId, $fileName, $coords, $file, $pixelDimensions)
    {
        $overlay = new GeoOverlay;
        $overlay->volume_id = $volumeId;
        $overlay->name = $fileName;
        $overlay->top_left_lng = number_format($coords[0], 13);
        $overlay->top_left_lat = number_format($coords[1], 13);
        $overlay->bottom_right_lng = number_format($coords[2], 13);
        $overlay->bottom_right_lat = number_format($coords[3], 13);
        $overlay->browsing_layer = false;
        $overlay->context_layer = false;
        $overlay->attrs = ["width" => $pixelDimensions[0],  "height" => $pixelDimensions[1]];
        $overlay->save();
        $overlay->storeFile($file);
        $this->submitTileJob($overlay);

        return $overlay;
    }

    /**
     * Submit a new tile job for any new overlay.
     *
     * @param GeoOverlay $overlay
     */
    protected function submitTileJob(GeoOverlay $overlay)
    {
        $targetPath =  "{$overlay->id}/{$overlay->id}_tiles";
        TileSingleOverlay::dispatch($overlay, config('geo.tiles.overlay_storage_disk'), $targetPath);
    }


    /**
     * Update the context_layer and/or browsing_layer values
     * 
     * @api {put} volumes/:id/geo-overlays/geotiff/:geo_overlay_id Update a geotiff geo overlay
     * @apiGroup Geo
     * @apiName VolumesUpdateGeoTiff
     * @apiPermission projectAdmin
     * 
     * @apiParam (Attributes that can be updated) {Boolean} browsing_layer Defines whether to show the geoTIFF as a browsing-visualisation layer.
     * @apiParam (Attributes that can be updated) {Boolean} context_layer Defines whether to show the geoTIFF as a context-fusion layer.
     * 
     * @apiParamExample {String} Request example:
     * browsing_layer: true
     * context_layer: false
     * 
     * @param UpdateGeotiffOverlay $request
     * @param $geo_overlay_id
     */

     public function updateGeoTiff(UpdateGeotiffOverlay $request)
     {
         $overlay = GeoOverlay::findOrFail($request->geo_overlay_id);
         if($request->filled('context_layer') || $request->filled('browsing_layer')) {
             if($request->filled('context_layer')) {
                 $overlay->update([
                     'context_layer' => $request->input('context_layer')
                 ]);
             }
             if($request->filled('browsing_layer')) {
                 $overlay->update([
                     'browsing_layer' => $request->input('browsing_layer')
                 ]);
             }
             return response()->json([
                 'browsing_layer' => $overlay->browsing_layer,
                 'context_layer' =>  $overlay->context_layer
             ]);
         } else {
             return response('no data update performed', $status=422);
         }
     }
}
