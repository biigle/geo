<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Storage;
use Biigle\Volume;
use Illuminate\Http\Response;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Http\Requests\UpdateOverlay;

class GeoOverlayController extends Controller
{

    /**
     * Update the browsing_layer values
     * 
     * @api {put} volumes/:id/geo-overlays/:geo_overlay_id Update a geo overlay
     * @apiGroup Geo
     * @apiName VolumesUpdateGeoOverlay
     * @apiPermission projectAdmin
     * 
     * @apiParam (Attributes that can be updated) {Boolean} browsing_layer Defines whether to show the geoOverlay as a browsing-visualisation layer.
     * 
     * @apiParamExample {String} Request example:
     * browsing_layer: true
     * 
     * @param UpdateOverlay $request
     * @param $geo_overlay_id
     *
     * @return GeoOverlay updated overlay
     */
    public function updateGeoOverlay(UpdateOverlay $request)
    {
        $overlay = GeoOverlay::findOrFail($request->geo_overlay_id);

        if ($request->has('layer_index')) {
            $overlay->update([
                'layer_index' => $request->input('layer_index')
            ]);
        }

        if ($request->has('browsing_layer') && $overlay->browsing_layer != $request->browsing_layer) {
            $overlay->update([
                'browsing_layer' => $request->input('browsing_layer')
            ]);
        }

        return $overlay;
    }

    /**
     * Returns overlay data
     * 
     * @param int $id Volume id
     * 
     * @return Response
     */
    public function getOverlay(int $id)
    {
        $volume = Volume::findOrFail($id);
        $this->authorize('access', $volume);

        if ($volume->isVideoVolume()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $overlays = GeoOverlay::where('volume_id', $volume->id)
            ->where('browsing_layer', '=', true)
            ->orderBy('layer_index')
            ->get();

        $urlTemplate = Storage::disk(config('geo.tiles.overlay_storage_disk'))->url(':id/:id_tiles/');

        return response([
            'geoOverlays' => $overlays,
            'urlTemplate' => $urlTemplate
        ]);
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
