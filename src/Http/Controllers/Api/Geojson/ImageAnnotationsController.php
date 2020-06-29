<?php
namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use DB;
use Biigle\Image;
use GeoJson\Feature\{Feature, FeatureCollection};
use GeoJson\Geometry\Point;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class ImageAnnotationsController extends Controller{

  public function index($id)
  {
    $image = Image::findOrFail($id);
    $metadata = $image->metadata;
    $image_width_m = 2 * (float)$image->metadata['distance_to_ground'];
    $result = $image->annotations->map(function($annotation) use($image, $metadata,$image_width_m){
      $annotation_points = array_slice($annotation->points, 0, 2);
      $image_center = [($image->width)/2, ($image->height)/2];

      # Finding Annotation Point with respect to center of the image.
      $annotation_point = [($annotation_points[0] - $image_center[0]), ($image_center[1] - $annotation_points[1])];

      $rotated_X = $annotation_point[0] * cos((Float)$metadata['yaw']) + $annotation_point[1] * sin((Float)$metadata['yaw']);
      $rotated_Y = $annotation_point[0] * sin((Float)$metadata['yaw']) + $annotation_point[1] * cos((Float)$metadata['yaw']);

      $scaling_factor = $image_width_m/$image->width;

      # Coordinate Offset in Meters
      $coordinate_offset_meters = array_map(function($point) use($scaling_factor){return $point * $scaling_factor;}, [$rotated_X, $rotated_Y]);
      // $coordinate_offset_meters = array_map(function($point) use($scaling_factor){return $point * $scaling_factor;}, $annotation_point);

      #radius of Earth
      $R = 6378137;

      # Coordinate Offset in Radian
      $lat_radian = $coordinate_offset_meters[1]/$R;
      $lng_radian =  $coordinate_offset_meters[0]/($R*cos((pi()*$image->lng)/180));

      # Shift the latitude and longitude to annotation point.
      $new_lat = $image->lat + $lat_radian * 180/pi();
      $new_lng = $image->lng + $lng_radian * 180/pi();
      $features = new Feature(new Point([$new_lng, $new_lat]));
      return $features;
    });
    $result->push(new Feature(new Point([$image->lng, $image->lat])));
    return new FeatureCollection($result->all());
  }
}
?>
