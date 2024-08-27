<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Modules\Geo\WebMapOverlay;
use Illuminate\Http\Request;
use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Http\Requests\StoreWebMapOverlay;
use Biigle\Modules\Geo\Http\Requests\UpdateOverlay;
use Biigle\Modules\Geo\Services\Support\WebMapSource;
use Illuminate\Validation\ValidationException;

class WebMapOverlayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWebMapOverlay $request)
    {
        $validated = $request->validated();
        $volumeId = $request->input('volumeId');
        $webmapSource = new WebMapSource($request->input('url'));

        // Check whether provided url is base url or query-url
        // 1. Case --> base URL given
        // Steps: perform getCapablities lookup and extract first layer
        if(!$webmapSource->isQueryUrl()) {
            [$webmapTitle, $webmapLayers] = $webmapSource->firstValidLayer();
            $overlay = $this->saveWebMapOverlay($volumeId, $webmapSource->baseUrl, $webmapTitle, $webmapLayers);
        } else {
            // 2. Case --> specific URL with query parameters given (e.g. '?service=wms&version=1.1.0&request=GetMap&layers=CV_Acc...')
            // Steps: Extract layer from layers parameter in url
            $webmapLayers = $webmapSource->extractLayersFromQueryUrl();
            // if no layers could be found in the url query-string
            if(is_null($webmapLayers)) {
                // use getCapabilities-request to find layer (like first case)
                [$webmapTitle, $webmapLayers] = $webmapSource->firstValidLayer();
            } else {
                // overwrite layerString with only first layer (search for title of this layer in getLayerTitle)
                $firstLayerName = $webmapLayers[0];
                    // get corresponding layer-title from webmapSource
                $webmapTitle = $webmapSource->getLayerTitle($firstLayerName);
            }

            // check whether baseURL exists already in DB
            $existingWms = WebMapOverlay::where('volume_id', $volumeId)->pluck('url', 'name')->all();
            // $existingUrls
            if (in_array($webmapSource->baseUrl, array_values($existingWms))) {
                // strip the url if too long
                $urlShort = strlen($webmapSource->baseUrl) > 80 ? substr($webmapSource->baseUrl, 0, 80) . "..." : $webmapSource->baseUrl;
                $wmsTitle = array_search($webmapSource->baseUrl, $existingWms);
                throw ValidationException::withMessages(
                    [
                        'uniqueUrl' => ["The url \"{$urlShort}\" has already been uploaded (Filename: \"{$wmsTitle}\")."],
                    ]
                );
            }
            // save the WebMapOverlay to DB
            $overlay = $this->saveWebMapOverlay($volumeId, $webmapSource->baseUrl, $webmapTitle, $webmapLayers);
        }
        
        return $overlay;
    }

    /**
     * Save webmap data in WebMapOverlay DB
     *
     * @param $volumeId ID of the current volume
     * @param $url The base url of the WMS resource
     * @param $title The title of the WMS resource (gets displayed to the user)
     * @param $layer The layer-name of the WMS resource
     *
     * @return WebMapOverlay
     */
    protected function saveWebMapOverlay($volumeId, $url, $title, $layers)
    {
        $overlay = new WebMapOverlay;
        $overlay->volume_id = $volumeId;
        $overlay->url = $url;
        $overlay->name = $title;
        $overlay->layers = $layers;
        $overlay->browsing_layer = false;
        $overlay->context_layer = false;
        $overlay->save();
        $overlay->type = 'webmap';

        return $overlay;
    }
}
