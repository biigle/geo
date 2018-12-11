<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Response;
use Biigle\Volume;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Http\Controllers\Api\Controller;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

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
     * @return \Illuminate\Http\Response
     */
    public function showFile($id)
    {
        $overlay = GeoOverlay::findOrFail($id);
        $this->authorize('access', $overlay->volume);

        try {
            return Response::download($overlay->path);
        } catch (FileNotFoundException $e) {
            // source file not readable; nothing we can do about it
            abort(404, 'The geo overlay file does not exist.');
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
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $overlay = GeoOverlay::findOrFail($id);
        $this->authorize('update', $overlay->volume);

        $overlay->delete();
    }
}
