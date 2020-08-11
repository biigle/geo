@extends('manual.base')

@section('manual-title', 'Filtering data points in QGIS')

@section('manual-content')
  <div class="row">
    <p class="lead">
      QGIS include Filtering option through Attribute Table Interface and Query Builder Interface.
    </p>
    <p>
      The response data of GeoJSON Api endpoints also includes Attributes of each point. These attributes are viewable in QGIS Attribute Table and <a href="https://docs.qgis.org/3.10/en/docs/user_manual/working_with_vector/attribute_table.html">numerous operations can be performed</a>. The Filtered points are represented with different color on the map.
    </p>
    <p>
      One of the possible operation would be filtering data points by labels that occur in the images. This can be done through Attribute table.
    </p>
    <h3>Attribute Table</h3>
    <p><a href="https://docs.qgis.org/3.10/en/docs/user_manual/working_with_vector/attribute_table.html#introducing-the-attribute-table-interface" target="_blank">Open the Attribute Table Interface</a> and click <a href="https://docs.qgis.org/3.10/en/docs/user_manual/working_with_vector/attribute_table.html#filtering-and-selecting-features-using-forms" target="_blank">Filter/Select feature using form</a>.</p>
    <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/qgis_filtering_1.png')}}"><img src="{{asset('vendor/geo/images/manual/qgis_filtering_1.png')}}" width="90%"></a>
    </p>
    <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/qgis_filtering_2.png')}}"><img src="{{asset('vendor/geo/images/manual/qgis_filtering_2.png')}}" width="90%"></a>
    </p>
    <p>The form with the label attributes are visible. We can set the value of the. Now to filter the images which includes Crustasean label greater than 2 and does not have Sponge Label</p>
    <ul>
      <li>Set Crustasean input field to 2 and select "Greater than (>)" from the drop down</li>
      <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/qgis_filtering_3.png')}}"><img src="{{asset('vendor/geo/images/manual/qgis_filtering_3.png')}}" width="90%"></a>
      </p>
      <li>for Sponge select "Is missing (null)" from the drop down</li>
      <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/qgis_filtering_4.png')}}"><img src="{{asset('vendor/geo/images/manual/qgis_filtering_4.png')}}" width="90%"></a>
      </p>
      <li>After setting filtering options, click "Select Feature" to select the matching features.</li>
      <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/qgis_filtering_5.png')}}"><img src="{{asset('vendor/geo/images/manual/qgis_filtering_5.png')}}" width="90%"></a>
      </p> 
    </ul> 
    <p>The Yellow colored points are the images with Crustasean label greater than 2 and does not have Sponge Label.</p>
    <h3>Query Builder Interface</h3>
    <p>Another way of filtering option in QGIS is using <a href="https://docs.qgis.org/3.10/en/docs/user_manual/working_with_vector/vector_properties.html#query-builder">Query Builder</a>, which filters and displays only resulting points on map.</p>
    <ul>
      <li>Right Click on the choosen Layer and select "Filter..." option</li>
      <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/qgis_filtering_6.png')}}"><img src="{{asset('vendor/geo/images/manual/qgis_filtering_6.png')}}" width="90%"></a>
      </p>
      <li>In the section "Provider Specific Filter Expression", query condition can be build as shown in the image below.</li>
      <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/qgis_filtering_8.png')}}"><img src="{{asset('vendor/geo/images/manual/qgis_filtering_8.png')}}" width="90%"></a>
      </p>
      <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/qgis_filtering_9.png')}}"><img src="{{asset('vendor/geo/images/manual/qgis_filtering_9.png')}}" width="90%"></a>
      </p>
    </ul>
  </div>
@endsection