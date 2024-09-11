<?php

namespace Biigle\Modules\Geo\Services\Support;

use Illuminate\Validation\ValidationException;

class WebMapSource 
{
    /**
     * The raw url input string
     * 
     * @var String
     */
    protected $rawUrl;

    /**
     * The xml object from getCapabilities request
     * 
     * @var Object
     */
    protected $xml;
    
    /**
     * The parsed web-map-service url compartments
     *
     * @var Array
     */
    public $parsedUrl;

    /**
     * The base url String (without any query parameters)
     * 
     * @var String
     */
    public $baseUrl;



    /**
     * Create a new WebMapSource instance.
     *
     * @param String $url The uploaded raw url.
     *
     * @return void
     */
    public function __construct(String $url)
    {
        $this->rawUrl = $url;
        $this->parsedUrl = parse_url($url);
        $this->baseUrl = $this->unparseUrlBase();
        $this->xml = $this->getCapabilities();
    }

    /**
     * Checks if the url contains any query-parameters
     * 
     * @return Boolean 
     */
    public function isQueryUrl()
    {
        return !is_null(parse_url($this->rawUrl, PHP_URL_QUERY));
    }

    /**
     * Re-assemble a parsed url and return only the url-base
     * 
     * @return String
     */
    protected function unparseUrlBase()
    {
        $scheme   = isset($this->parsedUrl['scheme']) ? $this->parsedUrl['scheme'] . '://' : '';
        $host     = isset($this->parsedUrl['host']) ? $this->parsedUrl['host'] : '';
        $port     = isset($this->parsedUrl['port']) ? ':' . $this->parsedUrl['port'] : '';
        $user     = isset($this->parsedUrl['user']) ? $this->parsedUrl['user'] : '';
        $pass     = isset($this->parsedUrl['pass']) ? ':' . $this->parsedUrl['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($this->parsedUrl['path']) ? $this->parsedUrl['path'] : '';
        // return only the base url without query or fragment parameters
        $query    = isset($this->parsedUrl['query']) ? '?' . $this->parsedUrl['query'] : '';
        $fragment = isset($this->parsedUrl['fragment']) ? '#' . $this->parsedUrl['fragment'] : '';

        return "$scheme$user$pass$host$port$path";
    }

    /**
     * Checks whether the base-url is an actual WMS resource and sets global XML variable
     * 
     * @return Boolean 
     */
    protected function getCapabilities()
    {
        $wmsRequest = file_get_contents($this->baseUrl.'?service=wms&version=1.1.1&request=GetCapabilities');
        libxml_use_internal_errors(true); // suppress all XML errors
        $xml = simplexml_load_string($wmsRequest);
        if($xml === false) {
            throw ValidationException::withMessages(
                [
                    'invalidWMS' => ["The url does not lead to a WMS resource."],
                ]
            );
        } else {
            return $xml;
        }
    }

    /**
     * Search the getCapabilities xml and find the first valid layer of the wms-resource
     * 
     * @return Array
     */
    public function firstValidLayer()
    {
        // select only those layers that have no Child layers within them
        $layers = $this->xml->xpath('//*[local-name()="Layer"][not(.//*[local-name()="Layer"])]');
        // loop over layers and return first valid layer title and name
        foreach($layers as $layer) {
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
        throw ValidationException::withMessages(
            [
                'noValidLayer' => ["Could not find any valid layers within the WMS resource."],
            ]
        );
    }

    /**
     * Finds the corresponding Title to a WMS Layer-Name (defaults to input name)
     * 
     * @param $layerString A Layer Name of the WMS
     * @return String
     */
    public function getLayerTitle($layerString)
    {
        // xpath query to find the corresponding layer-title in the getCapabilities xml
        $titleArray = $this->xml->xpath('(//*[local-name()="Layer"]/*[Name="' . $layerString .'"])[1]/Title');
        if(count($titleArray) !== 0) {
            $webmapTitle = (string) $titleArray[0];
        } else { // default case
            $webmapTitle = $layerString;
        }
        return $webmapTitle;
    }

    /**
     * Takes the query-string of the url and extracts the layer parameter
     * 
     * Example: https://.../CONMAR/wms?service=WMS&version=1.1.0&request=GetMap&layers=CONMAR,3A_04e_wrecks&bbox=6.1...
     * method would return array ['CONMAR', '3A_04e_wrecks']
     * 
     * @return null,Array
     */
    public function extractLayersFromQueryUrl()
    {
        $queryString = $this->parsedUrl['query'];
        // split the query-string into its compartments
        parse_str(urldecode($queryString), $output);

        // if queryString is empty or does not contain the layers parameter
        if(empty($queryString) || empty($output['layers'])) {
            return null;
        } else {
            // Extract layers from url query-string
            $layerString = $output['layers'];
            // if multiple layers are defined in url layers-parameter:
            if(str_contains($layerString, ',')) {
                $webmapLayers = explode(',', $layerString);
            } else {
                // if $layerString contains only one layer
                $webmapLayers = [$layerString];
            }
            return $webmapLayers;
        }
    }


}