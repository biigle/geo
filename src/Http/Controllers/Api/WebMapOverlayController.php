<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Modules\Geo\Http\Requests\StoreWebMapOverlay;
use Exception;
use Illuminate\Validation\ValidationException;

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

        $overlay = GeoOverlay::build($volumeId, $webmapTitle, 'webmap', [$webmapSource->baseUrl, $webmapLayers]);
        return $overlay->fresh();
    }
}
