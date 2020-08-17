@extends('manual.base')

@section('manual-title', 'Importing Individual Annotation Labels Position')

@section('manual-content')
  <div class="row">
    <p class="lead">
      BIIGLE also includes an API endpoint that positions single annotation labels on the world map. 
    </p>
    <p>BIIGLE includes APIs that specifically returns the position of every annotation labels in an image or images within the Project or Volume.</p>
    <p>Following is a screenshot of response data from Annotations API in Attribute table.</p>
    <p class="text-center">
      <a href="{{asset('vendor/geo/images/manual/annotations-position.png')}}"><img src="{{asset('vendor/geo/images/manual/annotations-position.png')}}" width="90%"></a>
    </p>
    <p>In order for API to compute the position of annotation labels, the images uploaded to BIIGLE should contain following attributes</p>
    <table class="table">
      <thead>
        <tr>
          <th>Attribute</th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <code>Width</code>
          </td>
          <td>
            Width of the uploaded Image.
          </td>
        </tr>
        <tr>
          <td>
            <code>Height</code>
          </td>
          <td>Height of the uploaded Image.</td>
        </tr>
        <tr>
          <td>
            <code>distance_to_ground</code>
          </td>
          <td>Distance to the sea floor in meters.</td>
        </tr>
        <tr>
          <td>
            <code>yaw</code>
          </td>
          <td>The yaw/heading in degrees of the underwater vehicle. 0° yaw should be north.</td>
        </tr>
        <tr>
          <td>
            <code>lat</code>
          </td>
          <td>Longitude where the image was taken in decimal form. If this column is present, lat must be present, too.</td>
        </tr>
        <tr>
          <td>
            <code>lng</code>
          </td>
          <td>Latitude where the image was taken in decimal form. If this column is present, lng must be present, too. Example: 28.775</td>
        </tr>
        <tr>
          <td>
            <code>filename</code>
          </td>
          <td>The filename of the image the metadata belongs to.</td>
        </tr>
      </tbody>
    </table>
    <p>When Capturing the image camera must have an opening angle of 90&#176; and the "yaw" should be specified in degree, where 0&#176; points to north, 90&#176; points to east, 180&#176; points to south and 270&#176; points to west. So, the actual width of the sea floor captured in the image is twice the "distance_to_ground". </p>
    
    <p>Following steps were take to compute the position of annotation labels:</p>
    <ol>
      <li>Find center of the image in pixel.</li>
      <p>image_center = (image_width/2, image_height/2)</p>
      <li>Find annotation point with respect to center of the image ( in pixel ).<br>
        <i>Note: <a href="https://biigle.de/doc/api/index.html#api-Annotations-StoreImageAnnotations">when creating new annotation in image annotation point is also is also stored, but this is not with respect to center of image.</a></i></li>
      <li>Rotate the annotation point according to "Yaw". ( in pixel )</li>
      <li>Convert rotated annotation point to coordinate offset in meters by multiplying the points with scaling_factor.</li>
      <p>i.e. scaling_factor = (width of sea floor captured)/(image_width) <br> and Width of sea floor = 2 * distance_to_ground</p>
      <li>Coordinate (latitude and longitude) Offset are converted to Radian</li>
      <li>The image "lat" and "lng" are shifted to according to coordinate offset as follows"</li>
      <p>annotation label lat = (image->lat) + (latitude offset in radian * 180/pi) <br>
        annotation label lng = (image->lng) + (longitude offset in radian * 180/pi)</p>
    </ol>
    <p>This shift gives the latitude and longitude of Annotations labels.</p>
    <p class="text-center">
      <a href="{{asset('vendor/geo/images/manual/annotation-position-calculation.png')}}"><img src="{{asset('vendor/geo/images/manual/annotation-position-calculation.png')}}" width="90%"></a>
    </p>
  </div>
@endsection