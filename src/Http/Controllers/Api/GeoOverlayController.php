<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Modules\Geo\Http\Requests\UpdateOverlay;
use League\Flysystem\UnableToRetrieveMetadata;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Biigle\Volume;
use Storage;

class GeoOverlayController extends Controller
{
    
    /**
     * Shows the geo overlays of the specified volume.
     *
     * @api {get} volumes/:id/geo-overlays Get geo overlays
     * @apiGroup Geo
     * @apiName VolumesIndexGeoOverlays
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
     * Shows the specified geo overlay file.
     *
     * @api {get} geo-overlays/:id/file Get a geo overlay file
     * @apiGroup Geo
     * @apiName ShowGeoOverlayFile
     * @apiPermission projectMember
     *
     * @apiParam {Number} id The geo overlay ID.
     *
     * @param int $id geo overlay id
     * @return mixed
     */
    public function showFile($id)
    {
        $overlay = GeoOverlay::findOrFail($id);
        $this->authorize('access', $overlay->volume);

        try {
            return Storage::disk(config('geo.tiles.overlay_storage_disk'))
                ->download($overlay->path);
        } catch (UnableToRetrieveMetadata $e) {
            abort(404);
        }
    }

    /**
     * Update the context_layer and/or browsing_layer values
     * 
     * @api {put} volumes/:id/geo-overlays/:geo_overlay_id Update a geo overlay
     * @apiGroup Geo
     * @apiName VolumesUpdateGeoOverlay
     * @apiPermission projectAdmin
     * 
     * @apiParam (Attributes that can be updated) {Boolean} browsing_layer Defines whether to show the geoOverlay as a browsing-visualisation layer.
     * @apiParam (Attributes that can be updated) {Boolean} context_layer Defines whether to show the geoOverlay as a context-fusion layer.
     * 
     * @apiParamExample {String} Request example:
     * browsing_layer: true
     * context_layer: false
     * 
     * @param UpdateOverlay $request
     * @param $geo_overlay_id
     */

     public function updateGeoOverlay(UpdateOverlay $request)
     {
        $overlay = GeoOverlay::findOrFail($request->geo_overlay_id);
        if($request->filled('layer_type')) {
            if($request->input('layer_type') == 'contextLayer') {
                $overlay->update([
                    'context_layer' => $request->input('value')
                ]);
            }
            if($request->input('layer_type') == 'browsingLayer') {
                $overlay->update([
                    'browsing_layer' => $request->input('value')
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

    /**
     * Deletes the geo overlay.
     *
     * @api {delete} geo-overlays/:id Delete a geo overlay
     * @apiGroup Geo
     * @apiName DestroyGeoOverlay
     * @apiPermission projectAdmin
     *
     * @apiParam {Number} id The geo overlay ID.
     *
     * @param int $id geo overlay id
     */
    public function destroy($id)
    {
        $overlay = GeoOverlay::findOrFail($id);
        $this->authorize('update', $overlay->volume);

        $overlay->delete();
    }
}
