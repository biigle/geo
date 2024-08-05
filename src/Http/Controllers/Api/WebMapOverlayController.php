<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Modules\Geo\WebMapOverlay;
use Illuminate\Http\Request;
use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Http\Requests\StoreWebMapOverlay;
use Biigle\Modules\Geo\Http\Requests\UpdateOverlay;
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
        $webmapUrl = $request->input('url');
        $volumeId = $request->input('volumeId');
        $parsed_url = parse_url($webmapUrl);

        // Check whether provided url is base url or query-url
        // 1. Case --> base URL given
        // Steps: perform getCapablities lookup and extract first layer
        if(is_null(parse_url($webmapUrl, PHP_URL_QUERY))) {
            $baseUrl = $this->unparseUrl($parsed_url);
            $xmlResult = $this->getCapabilities($baseUrl);
            [$webmapTitle, $webmapLayers] = $this->firstValidLayer($xmlResult);
            $overlay = $this->saveWebMapOverlay($volumeId, $baseUrl, $webmapTitle, $webmapLayers);
        } else {
            // 2. Case --> specific URL with query parameters given (e.g. '?service=wms&version=1.1.0&request=GetMap&layers=CV_Acc...')
            // Steps: Extract layer from layers parameter in url
            $baseUrl = $this->unparseUrl($parsed_url, $base = true);
            $xmlResult = $this->getCapabilities($baseUrl);
            $queryString = $parsed_url['query'];
            
            // split the query-string into its compartments
            parse_str(urldecode($queryString), $output);
            // if queryString is empty or does not contain the layers parameter, use getCapabilities-request to find layer (like first case)
            if(empty($queryString) || empty($output['layers'])) {
                [$webmapTitle, $webmapLayers] = $this->firstValidLayer($xmlResult);
            } else {
                // Extract layers from url query-string
                $layerString = $output['layers'];
                // if multiple layers are defined in url layers-parameter:
                if(str_contains($layerString, ',')) {
                    $webmapLayers = explode(',', $layerString);
                    // overwrite layerString with only first layer (searches for title of this layer in getCapabilities)
                    $layerString = $webmapLayers[0];
                } else {
                    // if $layerString contains only one layer
                    $webmapLayers = [$layerString];
                }
                
                // xpath query to find the corresponding layer-title in the getCapabilities xml
                $titleArray = $xmlResult->xpath('(//*[local-name()="Layer"]/*[Name="' . $layerString .'"])[1]/Title');
                if(count($titleArray) !== 0) {
                    $webmapTitle = (string) $titleArray[0];
                } else { // default case
                    $webmapTitle = $layerString;
                }
            }

            // check whether baseURL exists alread in DB
            $existingUrls = WebMapOverlay::where('volume_id', $volumeId)->pluck('url')->toArray();
            if (in_array($baseUrl, $existingUrls)) {
                // strip the url if too long
                $urlShort = strlen($baseUrl) > 80 ? substr($baseUrl, 0, 80) . "..." : $baseUrl;
                throw ValidationException::withMessages(
                    [
                        'uniqueUrl' => ["The url \"{$urlShort}\" has already been uploaded (Filename: \"{$webmapTitle}\")."],
                    ]
                );
            }
            // save the WebMapOverlay to DB
            $overlay = $this->saveWebMapOverlay($volumeId, $baseUrl, $webmapTitle, $webmapLayers);
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

    protected function firstValidLayer($xmlResult)
    {
        // select only those layers that have no Child layers within them
        $layers = $xmlResult->xpath('//*[local-name()="Layer"][not(.//*[local-name()="Layer"])]');
        // loop over layers and return first valid layer title and name
        foreach($layers as $layer) {
            if((string) $layer['queryable'] === "1") {
                $webmapTitle = (string) $layer->Title;
                // Excerpt from OpenGIS 'Web Map Server Implementation Specification':
                // If, and only if, a layer has a <Name>, then it is a map layer that can be requested
                // If the layer has a Title but no Name, then that layer is only a category title for
                // all the layers nested within (the latter case should not occur due to xpath query above)
                if(!empty($layer->Name)) {
                    $webmapLayers = [(string) $layer->Name];
                    return [$webmapTitle, $webmapLayers];
                }
            }
        }
        throw ValidationException::withMessages(
            [
                'noValidLayer' => ["Could not find any valid layers within the WMS resource."],
            ]
        );
    }
    
    /**
     * Checks whether the base-url is an actual WMS resource and returns 'getCapabilities' XML
     */
    protected function getCapabilities($baseUrl)
    {
        $wmsRequest = file_get_contents($baseUrl.'?service=wms&version=1.1.1&request=GetCapabilities');
        libxml_use_internal_errors(true); // suppress all XML errors
        $xml = simplexml_load_string($wmsRequest);
        if($xml === false) {
            throw ValidationException::withMessages(
                [
                    'invalidWMS' => ["The url does not lead to a WMS resource."],
                ]
            );
        }
        return $xml;
    }

    /**
     * Re-assemble a parsed url
     */
    protected function unparseUrl($parsed_url, $base = false)
    {
        // return only the base url
        if($base == true) {
            unset($parsed_url['fragment']);
            unset($parsed_url['query']);
        }

        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Display the specified resource.
     */
    public function show(WebMapOverlay $webMapOverlay)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WebMapOverlay $webMapOverlay)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOverlay $request)
    {
        $overlay = WebMapOverlay::findOrFail($request->webmap_overlay_id);
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
     * Remove the specified resource from storage.
     * 
     * @param int $id webmap overlay id
     */
    public function destroy($id)
    {
        $overlay = WebMapOverlay::findOrFail($id);
        $this->authorize('update', $overlay->volume);

        $overlay->delete();
    }
}
