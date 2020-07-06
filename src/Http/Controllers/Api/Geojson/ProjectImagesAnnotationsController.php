<?php
namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use DB;
use Biigle\Project;
use Biigle\Image;
use GeoJson\Feature\{Feature, FeatureCollection};
use GeoJson\Geometry\Point;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class ProjectImagesAnnotationsController extends Controller{

  public function index($id)
  {
    $project = Project::findOrFail($id);
    $this->authorize('access', $project);

    $images = Image::wherein("volume_id", $project->volumes->pluck('id')->all())
                      ->join('annotations', 'annotations.image_id', '=', 'images.id')
                      ->join('annotation_labels', 'annotation_labels.annotation_id', '=', 'annotations.id')
                      ->join('labels', 'labels.id', '=', 'annotation_labels.label_id');
    $results = $images->get()->map(function ($image) {
      $metadata = $image->metadata;
      $image_width_m = 2 * (float)$metadata['distance_to_ground'];
      $annotation_points = array_slice(json_decode($image->points), 0, 2);
      $image_center = [($image->width)/2, ($image->height)/2];

      # Finding Annotation Point with respect to center of the image.
      $annotation_point = [($annotation_points[0] - $image_center[0]), ($image_center[1] - $annotation_points[1])];

      // Yaw specifies the clockwise rotation in degrees but the formula below expects
      // the counterclockwise angle in radians.
      $yaw = deg2rad(-floatval($metadata['yaw']));

      # X and Y Coordinate Rotations According to Yaw
      $rotated_X = $annotation_point[0] * cos($yaw) - $annotation_point[1] * sin($yaw);
      $rotated_Y = $annotation_point[0] * sin($yaw) + $annotation_point[1] * cos($yaw);

      $scaling_factor = $image_width_m/$image->width;

      # Coordinate Offset in Meters
      $coordinate_offset_meters = array_map(function($point) use($scaling_factor){return $point * $scaling_factor;}, [$rotated_X, $rotated_Y]);
      #radius of Earth
      $R = 6378137;

      # Coordinate Offset in Radian
      $lat_radian = $coordinate_offset_meters[1]/$R;
      $lng_radian =  $coordinate_offset_meters[0]/($R*cos((pi()*$image->lat)/180));

      # Shift the latitude and longitude to annotation point.
      $new_lat = $image->lat + $lat_radian * 180/pi();
      $new_lng = $image->lng + $lng_radian * 180/pi();
      return new Feature(new Point([$new_lng, $new_lat]), array_merge(['_id' => $image->image_id,
      'Label ID' => $image->id, "Label Name"=>$image->name, "annotation coordinates" => "lat: {$new_lat}, lng:{$new_lng}",
      "image_coordinate" => "lat: {$image->lat}, lng: {$image->lng}", '_filename' => $image->filename]));
    });
    return new FeatureCollection($results->all());
  }
}
?>
