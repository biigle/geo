@extends('manual.base')

@section('manual-title') Geo Overlays @stop

@section('manual-content')
    <div class="row">
        <p class="lead">
            Geo Overlays are geo-referenced layers that can be used on maps to filter images.
        </p>
        <p>
            In the Geo Overlays panel, overlays can be created by uploading GeoTIFF files or linking web-map-services (WMS)
            via URLs.
            The panel is located in the Volume Edit View (click the <button class="btn btn-default btn-xs" title="Edit"><i
                    class="fa fa-pencil-alt"></i></button> button in the volume overview). It is displayed only if at least
            one image in the volume contains geo information.
            Show available upload options by using the <button class="btn btn-default btn-xs"><i
                    class="fa fa-plus"></i></button> button in the top-right corner of the Geo Overlays panel.
        </p>
        <h3>GeoTIFF</h3>
        <p>
            The first upload option is to provide a GeoTIFF file (.tif or .tiff). The upload allows only GeoTIFFs that have
            projected coordinate reference systems (CRS) and use the common <a href="https://epsg.org/home.html"
                target="_blank">EPSG Geodetic Parameter Dataset</a> codes (e.g. EPSG:4326 for WGS84 CRS). It does not
            support user-defined projected CRS. <br>
            After the upload, the GeoTIFF is tiled into lower-resolution JPG files for web-opimization. To ensure that the
            uploaded GeoTIFF is displayed as expected, check the following two properties:
        </p>
        <ol>
            <li>
                Make sure that the color-band of the GeoTIFF is normalized to the range of 0 to 255.
            </li>
            <li>
                The NoData value should be smaller than the minimum value of all color bands (e.g. -9999).
            </li>
        </ol>
        <h3>Web Map Service (WMS)</h3>
        <p>
            The second option to embed an overlay is by providing the URL to a WMS source. If only the base URL of the WMS
            is given, the first layer of the WMS is selected as the overlay.
        </p>
        <p>
            By providing an URL with query parameters, it is also possible to specify which layer of the WMS shall be used,
            e.g.,
        <div class="panel panel-info">
            <div class="panel-body">
                <code>https://example.com/ows?service=WMS&version=1.3.0&request=GetCapabilities&layers=LAYER</code>.
            </div>
        </div>
        </p>
        <h3>Overlay Usage</h3>
        <p>
            In the Geo Overlay panel, each overlay is listed with its name and a <button class="btn btn-default btn-xs"
                title="Show"><i class="fa fa-power-off"></i></button> button to show or hide it on the image volume map.
            It can be removed by using the corresponding <button type="button" class="close"
                style="float: none;">&times;</button> button.
        </p>
        <div class="panel panel-info">
            <div class="panel-body">
                <p>
                    Overlapping overlays can be displayed in a custom order on the volume map. To sort overlays, drag the
                    them via the <button class="btn btn-default btn-xs"><i class="fas fa-grip-lines"></i></button> button to
                    the desired position (highest = top) in the Geo Overlays panel.
                </p>
            </div>
        </div>
        <p>
            Images can be filtered by using the <a href="{{route('manual-tutorials', ['geo', 'volume-map'])}}">Geo Filter</a>
            in the volume overview.
        </p>
    </div>
@endsection