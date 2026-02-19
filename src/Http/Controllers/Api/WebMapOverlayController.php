<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Http\Requests\StoreWebMapOverlay;

class WebMapOverlayController extends Controller
{

    /**
     * Save Webmap as GeoOverlay
     *
     * @api {post} volumes/{id}/geo-overlays/webmap Save webmap to create a geo overlay
     * @apiGroup Geo
     * @apiName VolumesStoreWMS
     * @apiPermission projectAdmin
     *
     * @apiParam {String} Url to the webmap source
     *
     * @apiParamExample {String} Request example:
     * url: https://example.com
     *
     * @apiSuccessExample {json} Success response:
     * {
     * "id" => 1
     * "name" => "Title_0"
     * "type" => "webmap"
     * "browsing_layer" => true
     * "layer_index" => 1
     *   "attrs" => {
     *      "top_left_lng" => "-2.9198048485707",
     *      "top_left_lat" => "57.0974838573358",
     *      "bottom_right_lng" => "-2.9170062535908",
     *      "bottom_right_lat" => "57.0984589659731",
     *      "url" => https://example.com,
     *      "layer" => "Layer_1"
     *      }
     * }
     *
     * @param StoreWebMapOverlay $request
     * @return GeoOverlay
     */
    public function store(StoreWebMapOverlay $request)
    {
        $volumeId = $request->volume->id;
        $layerIdx = $request->input('layer_index');
        $webmapSource = $request->webmapSource;

        [$layerTitle, $layerName] = $webmapSource->getLayer();
        $coords = $webmapSource->getCoords($layerName);

        $overlay = GeoOverlay::build($volumeId, $layerName, 'webmap', $layerIdx, [$coords, $webmapSource->baseUrl, $layerTitle]);
        return $overlay->fresh();
    }
}
