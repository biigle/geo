@extends('manual.base')

@section('manual-title') Image volume map @stop

@section('manual-content')
<div class="row">
    <p class="lead">
        The image volume map shows the locations of images on a world map.
    </p>
    <p>
        If images of a volume contain the geo location in their <a href="{{route('manual-tutorials', ['volumes', 'image-metadata'])}}">metadata</a>, they can be disblayed on the volume map. For these volumes the <button class="btn btn-default btn-xs"><i class="fa fa-globe"></i></button> button appears in the sidebar of the volume overview. Click this button to view the image volume map. The volume map is not available for video volumes.
    </p>
    <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/volume_map_1.png')}}"><img src="{{asset('vendor/geo/images/manual/volume_map_1.png')}}" width="90%"></a>
    </p>
    <p>
        The locations of the images are displayed as dots on the map. Grab and move the map with the cursor to pan around or use the mouse wheel to zoom. Use the <i class="fa fa-compress"></i> button to fit the view back to the viewport that shows the locations of all images.
    </p>
    <h3>Annotation filter</h3>
    <p>
        You can filter the displayed locations by annotations that the images contain. This allows you to explore the spatial occurrence of different species or objects that you annotated earlier. Open the label trees tab at the right with a click on the <button class="btn btn-default btn-xs"><i class="fa fa-tags"></i></button> button. Now select a label from the label trees. The displayed locations will immediately update to show only images that contain annotations which have the selected label attached. You can also select multiple labels at the same time. The map will show the locations of images where <em>any</em> of the selected labels occur.
    </p>
    <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/volume_map_2.png')}}"><img src="{{asset('vendor/geo/images/manual/volume_map_2.png')}}" width="90%"></a>
    </p>
    <h3>Geo filter</h3>
    <p>
        The volume map allows you to filter the images displayed in the volume overview as well. To do this, activate the "geo selection" filter in the <button class="btn btn-default btn-xs"><i class="fa fa-filter"></i></button> filter tab of the volume overview. Then select image locations on the volume map by drawing an encompassing rectangle. To do this, press and hold <kbd>Ctrl</kbd> as well as the left mouse button and move the cursor on the map. Release the mouse button to finish the rectangle. You can also select individual locations with a single click or add locations to an existing selection with <kbd>Shift</kbd> and a single click.
    </p>
    <p class="text-center">
        <a href="{{asset('vendor/geo/images/manual/volume_map_3.png')}}"><img src="{{asset('vendor/geo/images/manual/volume_map_3.png')}}" width="49%"></a>
        <a href="{{asset('vendor/geo/images/manual/volume_map_4.png')}}"><img src="{{asset('vendor/geo/images/manual/volume_map_4.png')}}" width="49%"></a>
    </p>
    <p>
        Once there are selected image locations on the volume map, the volume overview will be immediately updated. You can use the geo filter in a "link and brush" way if you display the volume map and the volume overview side by side in two separate browser windows.
    </p>
</div>
@endsection
