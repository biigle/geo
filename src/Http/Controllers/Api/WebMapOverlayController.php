<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Exception;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Http\Controllers\Api\Controller;
use Illuminate\Validation\ValidationException;
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
     * "layer_index" => null
     *   "attrs" => {
     *      "top_left_lng" => "-2.9198048485707",
     *      "top_left_lat" => "57.0974838573358",
     *      "bottom_right_lng" => "-2.9170062535908",
     *      "bottom_right_lat" => "57.0984589659731",
     *      "url" => https://example.com,
     *      "layers" => [ 0 => "Layer_1" ]
     *      }
     * }
     *
     * @param StoreWebMapOverlay $request
     * @return GeoOverlay
     */
    public function store(StoreWebMapOverlay $request)
    {
        $volumeId = $request->volume->id;
        $webmapSource = $request->webmapSource;

        try {
            if ($webmapSource->isQueryUrl()) {
                $webmapLayers = $webmapSource->extractLayersFromQueryUrl();
                if ($webmapLayers) {
                    $firstLayerName = $webmapLayers[0];
                    $webmapTitle = $webmapSource->getLayerTitle($firstLayerName);
                } else {
                    [$webmapTitle, $webmapLayers] = $webmapSource->firstValidLayer();
                }
            } else {
                [$webmapTitle, $webmapLayers] = $webmapSource->firstValidLayer();
            }
        } catch (Exception $e) {
            throw ValidationException::withMessages(['noValidLayer' => "Could not find any valid layers within the WMS resource."]);
        }

        $firstLayerName = $webmapLayers[0];
        $coords = $webmapSource->getCoords($firstLayerName);
        $overlay = GeoOverlay::build($volumeId, $webmapTitle, 'webmap', [$coords, $webmapSource->baseUrl, $webmapLayers]);
        return $overlay->fresh();
    }
}
