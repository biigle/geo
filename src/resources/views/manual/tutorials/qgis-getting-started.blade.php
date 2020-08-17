@extends('manual.base')

@section('manual-title', 'Getting Started')

@section('manual-content')
  <div class="row">
    <p class="lead">Introduction on Interfacing BIIGLE API with QGIS.</p>
    <p>
      QGIS is an open source Geographic Information System that is used for viewing, editing and analysis of geospatial data. In order to interface BIIGLE with QGIS, API endpoints, which respond with GeoJSON format data, had been added. These API’s can only be accessed by all the members of the Project.
    </p>

    <p>
      The GeoJSON format data From the API can be imported to QGIS by creating a new "Vector Layer" in QGIS. In the following examples you will learn how to successfully configure the Authentication Credential for BIIGLE account in QGIS, add a "Vector Layer" and import Images data points of a volume whose id is specified in API URI.
    </p>
    <p><i>Note: For this manual, QGIS Version 3.10 (Long Term Release) is being used.</i></p>
    <p>First, Open QGIS Desktop and click on the "Layer" option in Menu.</p>
    <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/QGIS_1.jpeg')}}"><img src="{{asset('vendor/geo/images/manual/QGIS_1.jpeg')}}" width="100%" style="border: 1px solid #111;"></a>
    </p>
    <p>Then, Select "Layer->Add Layer->Add Vector Layer ( ctrl+shift+v )".</p>
    <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/QGIS_2.jpeg')}}"><img src="{{asset('vendor/geo/images/manual/QGIS_2.jpeg')}}" width="100%" style="border: 1px solid #111;"></a>
    </p>
    <p>This opens the "Data Source Manager" Dialog Box.</p>
    <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/QGIS_3.jpeg')}}"><img src="{{asset('vendor/geo/images/manual/QGIS_3.jpeg')}}" width="100%" style="border: 1px solid #111;"></a>
    </p>
    <p>In the Dialog Box,
      <ol>
        <li>select “Protocol, HTTP(s), cloud, etc” for Source Type.</li>
        <li> Leave the Encoding to “Automatic”</li>
        <li>In Protocol Type select “GeoJSON”</li>
        <li> Added Volume Images API URI "http://biigle.de/api/v1/geojson/volumes/1/images</li>
      </ol>
    </p>
    <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/QGIS_4.jpeg')}}"><img src="{{asset('vendor/geo/images/manual/QGIS_4.jpeg')}}" width="100%" style="border: 1px solid #111;"></a>
    </p>
    <p>In the Authentication Section, Click on “+” button. This Opens Authentication Dialog Box.
      <ul>
        <li>The “Name” field is optional.</li>
        <li>In the Username field type BIIGLE account email address. Note: replace “@” in the email with “%40”.</li>
        <li>Then, generate a new BIIGLE API token from settings -> <a href="http://biigle.de/settings/tokens">Token page</a> and add the token in the password field.</li>
        <li>Click Save.</li>
      </ul>
    </p>
    <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/QGIS_5.jpeg')}}"><img src="{{asset('vendor/geo/images/manual/QGIS_5.jpeg')}}" width="100%" style="border: 1px solid #111;"></a>
    </p>
    <p class="text-center">
      <a href="{{asset('vendor/geo/images/manual/QGIS_6.jpeg')}}"><img src="{{asset('vendor/geo/images/manual/QGIS_6.jpeg')}}" width="100%" style="border: 1px solid #111;"></a>
    </p>
    <p>Select the newly added Authentication configuration from dropdown and click Add.</p>
    <p class="text-center">
      <a href="{{asset('vendor/geo/images/manual/QGIS_7.jpeg')}}"><img src="{{asset('vendor/geo/images/manual/QGIS_7.jpeg')}}" width="100%" style="border: 1px solid #111;"></a>
    </p>
  </div>
@endsection
