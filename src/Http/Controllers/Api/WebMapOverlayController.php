<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Modules\Geo\WebMapOverlay;
use Illuminate\Http\Request;
use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Http\Requests\StoreWebMapOverlay;
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
        $webmapUrl = $request->input('url');
        $webmapName = $request->input('name');
        $volumeId = $request->volumeId;

        // check whether file exists alread in DB 
        $existingFileNames = WebMapOverlay::where('volume_id', $volumeId)->pluck('name')->toArray();
        if (in_array($webmapName, $existingFileNames)) {
            // strip the name if too long
            $fileNameShort = strlen($webmapName) > 25 ? substr($webmapName, 0, 25) . "..." : $webmapName;
            throw ValidationException::withMessages(
                [
                    'fileExists' => ["The WMS \"{$fileNameShort}\" has already been uploaded."],
                ]
            );
        }

        $parsed_url = parse_url($webmapUrl);
        // Check whether provided url is base url or query-url
        // 1. Case --> base URL given
        // Steps: 
        // - perform getCapablities lookup and extract first layer
        if(is_null(parse_url($webmapUrl, PHP_URL_QUERY))) {
            $baseUrl = $this->unparseUrl($parsed_url);
            $result = $this->getCapabilities($baseUrl);
            
        } else {
            // 2. Case --> specific URL with query parameters (?service=wms&version=1.1.0&request=GetMap&layers=CV_Acc...)
            // Steps: 
            // - Extract layer from layers-variable
            $baseUrl = $this->unparseUrl($parsed_url, $base = true);
            $result = $this->getCapabilities($baseUrl);
        }
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
    public function update(Request $request, WebMapOverlay $webMapOverlay)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WebMapOverlay $webMapOverlay)
    {
        //
    }
}
