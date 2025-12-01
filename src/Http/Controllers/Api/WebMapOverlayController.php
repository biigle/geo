<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Modules\Geo\Http\Requests\StoreWebMapOverlay;

class WebMapOverlayController extends Controller
{

    /**
     * Save Webmap as GeoOverlay
     *
     * @param StoreWebMapOverlay $request
     * @return GeoOverlay
     */
    public function store(StoreWebMapOverlay $request)
    {
        $volumeId = $request->input('volumeId');
        $webmapSource = $request->webmapSource;

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

        $overlay = GeoOverlay::build($volumeId, $webmapTitle, 'webmap', [$webmapSource->baseUrl, $webmapLayers]);
        return $overlay;
    }
}
