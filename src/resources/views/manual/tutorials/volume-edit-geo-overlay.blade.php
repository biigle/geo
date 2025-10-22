@extends('manual.base')

@section('manual-title') Geo overlays @stop

@section('manual-content')
<div class="row">
    <p class="lead">
        Geo overlays are geo-referenced layers that can be used on maps.
    </p>
    <p>
        In the geo overlays panel, overlays can be created by uploading geoTIFF-files or linking web-map-services (WMS) via URL's.
        The panel is located in the Volume Edit View (Click the <button class="btn btn-default btn-xs" title="Edit"><i class="fa fa-pencil-alt"></i></button>-button in the Volume Overview to get there). To view the available upload mechanisms in the geo overlays panel, click the <button class="btn btn-default btn-xs"><i class="fa fa-plus"></i></button>-button in the top-right corner of the panel.
    </p>
    <h3>GeoTIFF</h3>
    <p>
        The first option to upload an overlay is to provide a geoTIFF-file (in .tif(f) format). The upload allows only GeoTIFF's that have projected coordinate reference systems (CRS) and use the common <a href="https://epsg.org/home.html" target="_blank">EPSG Geodetic Parameter Dataset</a> codes (e.g. EPSG:4326 for WGS84 CRS). It does, however, not support user-defined projected CRS. <br>
        Note: When uploaded, the geoTIFF will be tiled into JPGEG files for web-opimization. Therefore, some information that is contained in the original .tiff will be lost. You should take one of the precautionary steps below to ensure the uploaded .tiff is displayed as expected in BIIGLE:
    </p>
    <ol>
        <li>
            Make sure that the color-band of the geoTIFF is normalized to the range of 0 to 255.
        </li>
        <li>
            If the color-band differs (e.g. negative range), the normalization can also be handled by BIIGLE if the NoData values of the geoTIFF are set to -99999.
        </li>
    </ol>
    <h3>Web Map Service (WMS)</h3>
    <p>
        The second option to embed an overlay is by providing the URL to a WMS source. If only the base URL of the WMS is provided (e.g. <code>https://maps.org/geoserver/namespace/wms</code>), the first layer of the WMS will be chosen as the overlay. By providing a URL with query parameters (e.g. <code>https://maps.org/geoserver/namespace/wms?service=WMS&version=1.1.0&request=GetMap&layers=LAYER1,LAYER5</code>), it is also possible to specify WHICH layer(s) of the WMS shall be used. The uploaded overlay will contain ALL the layers specified in the layers-parameter of the URL.
    </p>
    <h3>Overlay Usage</h3>
    <p>
        There are two possibilities to use the overlays within the Volume. The usage can be determined by enabling / disabling the "Browsing" and "Context" buttons within the list of overlays (individually for each uploaded overlay):
    </p>
    <ol>
        <li>
            An enabled "Browsing"-button will display the overlay on the map-modal of the geo-selection filter (Click the <button class="btn btn-default btn-xs"><i class="fa fa-filter"></i></button>-button in the Volume Overview and choose "geo-selection" from the list of filters to get there).
        </li>
        <li>
            An enabled "Context"-button makes the overlay available as a context-layer / background-layer in the Annotation-View of Biigle. 
        </li>
    </ol>
    <div class="panel panel-info">
        <div class="panel-body">
            <p>
                It is also possible to sort the overlays according to the order they should appear on the map (top to bottom = highest to lowest layer) by dragging an overlay from the list with the <button class="btn btn-default btn-xs"><i class="fas fa-grip-lines"></i></button>-button to the desired position. 
            </p>
        </div>
    </div>

</div>
@endsection