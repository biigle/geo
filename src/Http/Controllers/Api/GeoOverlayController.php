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
     * Update the browsing_layer or layer_index values for a set of overlays
     * 
     * @api {put} volumes/:id/geo-overlays Update a geo overlay
     * @apiGroup Geo
     * @apiName VolumesUpdateGeoOverlay
     * @apiPermission projectAdmin
     * 
     * @apiParam (Attributes that can be updated) {Boolean} browsing_layer Defines whether to show the geoOverlay as a browsing-visualisation layer.
     * @apiParam (Attributes that can be updated) {integer} layer_index Represents the layer position among all layers.
     * 
     * @apiParamExample {String} Request example:
     * [
     *     [
     *      'id' => 0,
     *      'name' => 'overlay_name',
     *      'volume_id' => 1,
     *      'layer_index' => 4
     *     ]
     * ]
     * 
     */
    public function updateGeoOverlays(UpdateOverlay $request)
    {
        $updated_overlays = $request->input('updated_overlays');
        GeoOverlay::upsert($updated_overlays, ['id'], [$request->updateKey]);
    }

    /**
     * Returns overlay data
     * 
     * @param int $id Volume id
     * 
     * @return Response
     */
    public function getOverlays(int $id)
    {
        $volume = Volume::findOrFail($id);
        $this->authorize('access', $volume);

        if ($volume->isVideoVolume()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $overlays = GeoOverlay::where('volume_id', $volume->id)
            ->where('processed', true)
            ->orderBy('layer_index', 'desc')
            ->get();

        $urlTemplate = Storage::disk(config('geo.tiles.overlay_storage_disk'))->url(':id/:id_tiles/');
        // Use the full template, since openalayer's default file extension is JPG
        $urlTemplate .= "{TileGroup}/{z}-{x}-{y}.png";

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
