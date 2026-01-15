<?php

namespace Biigle\Modules\Geo\Services\Support;

use Exception;

class WebMapSource extends Transformer
{
    /**
     * The raw url input string
     * 
     * @var string
     */
    protected $rawUrl;

    /**
     * The xml object from getCapabilities request
     * 
     * @var object
     */
    protected $xml;

    /**
     * The parsed web-map-service url compartments
     *
     * @var array
     */
    public $parsedUrl;

    /**
     * The base url String (without any query parameters)
     * 
     * @var string
     */
    public $baseUrl;

    /**
     * Use url to retrieve XML from source.
     *
     * @param string $url The uploaded raw url.
     *
     * @return void
     */
    public function useUrl(string $url)
    {
        $this->rawUrl = $url;
        $this->parsedUrl = parse_url($url);
        $this->baseUrl = $this->unparseUrlBase();
        $this->xml = $this->getCapabilities();
    }

    /**
     * Checks if the url contains any query-parameters
     * 
     * @return bool
     */
    public function isQueryUrl()
    {
        return !is_null(parse_url($this->rawUrl, PHP_URL_QUERY));
    }

    /**
     * Re-assemble a parsed url and return only the url-base
     * 
     * @return string
     */
    protected function unparseUrlBase()
    {
        $scheme = isset($this->parsedUrl['scheme']) ? $this->parsedUrl['scheme'] . '://' : '';
        $host = isset($this->parsedUrl['host']) ? $this->parsedUrl['host'] : '';
        $port = isset($this->parsedUrl['port']) ? ':' . $this->parsedUrl['port'] : '';
        $user = isset($this->parsedUrl['user']) ? $this->parsedUrl['user'] : '';
        $pass = isset($this->parsedUrl['pass']) ? ':' . $this->parsedUrl['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($this->parsedUrl['path']) ? $this->parsedUrl['path'] : '';

        return "$scheme$user$pass$host$port$path";
    }

    /**
     * Checks whether the base-url is an actual WMS resource and sets global XML variable
     *
     * @return \SimpleXMLElement xml from source
     */
    protected function getCapabilities()
    {
        $wmsRequest = $this->request($this->baseUrl . '?service=wms&version=1.1.1&request=GetCapabilities');
        libxml_use_internal_errors(true); // suppress all XML errors
        $xml = simplexml_load_string($wmsRequest);

        // xml can be a non-boolean value which is interpreted as boolean false
        if ($xml === false) {
            throw new Exception();
        }

        return $xml;
    }

    /**
     * Get coordinates of layer
     *
     * @param string $layerString Name of used layer
     *
     * @return array<array> Extent coordinates or empty array if no epsg is supported
     */
    public function getCoords($layerString)
    {
        $coord_info = $this->xml->xpath('(//*[local-name()="Layer"]/*[Name="' . $layerString . '"])[1]/BoundingBox[@SRS="EPSG:4326"]');

        if (count($coord_info) > 0) {
            $coord_info = (array) $coord_info[0];
            $coord_info = $coord_info['@attributes'];
            $res = [$coord_info['minx'], $coord_info['miny'], $coord_info['maxx'], $coord_info['maxy']];
            return $res;
        }

        $coord_info = $this->xml->xpath('(//*[local-name()="Layer"]/*[Name="' . $layerString . '"])[1]/BoundingBox');
        foreach ($coord_info as $arr) {
            $coords = (array) $arr;
            $coords = $coords['@attributes'];
            $pcrs = $coords['SRS'];
            if ($this->isProjected($pcrs)) {
                try {
                    $res = $this->transformToEPSG4326([$coords['minx'], $coords['miny'], $coords['maxx'], $coords['maxy']], $pcrs);
                    return $this->maybeFixCoords($res);
                } catch (Exception $e) {
                    // retry with next epsg code
                }
            }
        }
        return [];
    }

    /**
     * Request file from source
     *
     * @param string $url of source
     * @return bool|string request response
     */
    protected function request($url)
    {
        return file_get_contents($url);
    }

    /**
     * Search the getCapabilities xml and find the first valid layer of the wms-resource
     *
     * @return array containing the title and name of the layer.
     *
     * @throws Exception if xml contains no valid layer.
     */
    protected function firstValidLayer()
    {
        // select only those layers that have no Child layers within them
        $layers = $this->xml->xpath('//*[local-name()="Layer"][not(.//*[local-name()="Layer"])]');
        // loop over layers and return first valid layer title and name
        foreach ($layers as $layer) {
            $webmapTitle = (string) $layer->Title;
            // Excerpt from OpenGIS 'Web Map Server Implementation Specification':
            // If, and only if, a layer has a <Name>, then it is a map layer that can be requested
            // If the layer has a Title but no Name, then that layer is only a category title for
            // all the layers nested within (the latter case should not occur due to xpath query above)
            if (!empty($layer->Name)) {
                $webmapLayers = (string) $layer->Name;
                return [$webmapTitle, $webmapLayers];
            }
        }
        throw new Exception();
    }

    /**
     * Finds the corresponding Title to a WMS Layer-Name (defaults to input name)
     * 
     * @param $layerString A Layer Name of the WMS
     * @return string
     */
    protected function getLayerTitle($layerString)
    {
        // xpath query to find the corresponding layer-title in the getCapabilities xml
        $titleArray = $this->xml->xpath('(//*[local-name()="Layer"]/*[Name="' . $layerString . '"])[1]/Title');
        if (count($titleArray) !== 0) {
            $webmapTitle = (string) $titleArray[0];
        } else { // default case
            $webmapTitle = $layerString;
        }
        return $webmapTitle;
    }

    /**
     * Return layer name if it is present in the url.
     * 
     * @return null|string
     */
    protected function getLayerNameFromUrl()
    {
        parse_str(urldecode($this->parsedUrl['query']), $output);

        if (empty($output) || empty($output['layers'])) {
            return null;
        }

        return $output['layers'];
    }

    /**
     * Return layer title and name
     *
     * @return array
     */
    public function getLayer()
    {
        if($this->isQueryUrl()) {
            $layerName = $this->getLayerNameFromUrl();
            if ($layerName) {
                $webmapTitle = $this->getLayerTitle($layerName);
                return [$webmapTitle, $layerName];
            }
        }

        return $this->firstValidLayer();
    }


}
