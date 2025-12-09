@extends('manual.base')

@section('manual-title') Geo overlays @stop

@section('manual-content')
<div class="row">
    <p class="lead">
        Geo overlays are geo-referenced layers that can be used on maps to filter images.
    </p>
    <p>
        In the Geo Overlays panel, overlays can be created by uploading geoTIFF-files or linking web-map-services (WMS) via URLs.
        The panel is located in the Volume Edit View (click the <button class="btn btn-default btn-xs" title="Edit"><i class="fa fa-pencil-alt"></i></button> button in the volume overview). It is displayed only if at least one image in the volume contains geo information.
        Show available upload options by using the <button class="btn btn-default btn-xs"><i class="fa fa-plus"></i></button> button in the top-right corner of the Geo Overlays panel.
    </p>
    <h3>GeoTIFF</h3>
    <p>
        The first option to upload an overlay is to provide a geoTIFF-file (.tif or .tiff). The upload allows only GeoTIFFs that have projected coordinate reference systems (CRS) and use the common <a href="https://epsg.org/home.html" target="_blank">EPSG Geodetic Parameter Dataset</a> codes (e.g. EPSG:4326 for WGS84 CRS). It does not support user-defined projected CRS. <br>
        When uploaded, the geoTIFF is tiled into JPG files for web-opimization. Therefore, some information that is contained in the original geoTiff is lost. You should take one of the precautionary steps below to ensure the uploaded geoTiff is displayed as expected in BIIGLE:
    </p>
    <ol>
        <li>
            Make sure that the color-band of the geoTIFF is normalized to the range of 0 to 255.
        </li>
        <li>
            The NoData value should be smaller than the minimum value of all color bands (e.g. -9999).
        </li>
    </ol>
    <h3>Web Map Service (WMS)</h3>
    <p>
        The second option to embed an overlay is by providing the URL to a WMS source. If only the base URL of the WMS is provided the first layer of the WMS is selected as the overlay, e.g.,
        <div class="panel panel-info">
            <div class="panel-body">
                <code>https://example.com/geoserver/namespace/wms</code>.
            </div>
        </div>
    </p>
    <p>
        By providing a URL with query parameters it is also possible to specify WHICH layer(s) of the WMS shall be used. The uploaded overlay will contain ALL the layers specified in the layers-parameter of the URL, e.g.,
        <div class="panel panel-info">
            <div class="panel-body">
                <code>https://example.com/geoserver/namespace/wms?service=WMS&version=1.1.0&request=GetMap&layers=LAYER1,LAYER5</code>.
            </div>
        </div> 
    </p>
    <h3>Overlay Usage</h3>
    <p>
        In the Geo Overlay panel, each overlay is listed with its name and a <button class="btn btn-default btn-xs" title="Show"><i class="fa fa-power-off"></i></button> button to show or hide it on the map. 
        It can be removed by using the corresponding <button type="button" class="close">&times;</button> button.
    </p>
    <div class="panel panel-info">
        <div class="panel-body">
            <p>
                Overlapping overlays can be displayed in a certain order on the map. In the Geo Overlays panel, drag the overlay via the  <button class="btn btn-default btn-xs"><i class="fas fa-grip-lines"></i></button> button to the desired position (highest = top).
            </p>
        </div>
    </div>

    <p>
        Images can be filtered by using geo overlays in the volume overview via the <button class="btn btn-default btn-xs"><i class="fa fa-filter"></i></button> filter. Add a new filter by following the steps:
        <ol>
            <li>Select <button class="btn btn-default btn-xs">geo selection <i class="fa fa-angle-down"></i></button> in dropdown menu</li>
            <li>Click on the <button class="btn btn-default btn-xs">Add rule</button> button</li>
            <li>Show the overlay list by clicking on <button class="btn btn-default btn-xs"><i class="fas fa-layer-group"></i></button> and select overlay(s)</li>
        </ol>
    </p>

</div>
@endsection