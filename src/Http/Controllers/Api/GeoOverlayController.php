<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Storage;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class GeoOverlayController extends Controller
{
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
            return Storage::disk(config('geo.overlay_storage_disk'))
                ->download($overlay->path);
        } catch (FileNotFoundException $e) {
            abort(404);
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
