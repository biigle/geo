<?php
  namespace Biigle\Modules\Geo\Src\Libraries;

  use GeoJson\Feature\Feature;
  use GeoJson\Geometry\Point;
  /**
   *
   */
  class LabelCoordinates
  {

    function __construct($labels)
    {
      $this->labels = $labels;
    }

    function compute()
    {
      $result = $this->labels->map(function($label){
        $metadata = $label->metadata;
        $image_width_m = 2 * (float)$label->metadata['distance_to_ground'];
        $annotation_points = array_slice(json_decode($label->points), 0, 2);
        $image_center = [($label->width)/2, ($label->height)/2];

        # Finding Annotation Point with respect to center of the image.
        $annotation_point = [($annotation_points[0] - $image_center[0]), ($image_center[1] - $annotation_points[1])];

        // Yaw specifies the clockwise rotation in degrees but the formula below expects
        // the counterclockwise angle in radians.
        $yaw = deg2rad(-floatval($metadata['yaw']));

        # X and Y Coordinate Rotations According to Yaw
        $rotated_X = $annotation_point[0] * cos($yaw) - $annotation_point[1] * sin($yaw);
        $rotated_Y = $annotation_point[0] * sin($yaw) + $annotation_point[1] * cos($yaw);

        $scaling_factor = $image_width_m/$label->width;

        # Coordinate Offset in Meters
        $coordinate_offset_meters = array_map(function($point) use($scaling_factor){return $point * $scaling_factor;}, [$rotated_X, $rotated_Y]);

        #radius of Earth
        $R = 6378137;

        # Coordinate Offset in Radian
        $lat_radian = $coordinate_offset_meters[1]/$R;
        $lng_radian =  $coordinate_offset_meters[0]/($R*cos((pi()*$label->lat)/180));

        # Shift the latitude and longitude to annotation point.
        $new_lat = $label->lat + $lat_radian * 180/pi();
        $new_lng = $label->lng + $lng_radian * 180/pi();

        return new Feature(new Point([$new_lng, $new_lat]), array_merge(["Annotation Label ID" => $label->annotation_label_id,
        "image_id" => $label->image_id, "Label Name"=>$label->label_name, "annotation coordinates" => "lat: {$new_lat}, lng:{$new_lng}",
        "image_coordinate" => "lat: {$label->lat}, lng: {$label->lng}", 'Image filename' => $label->filename]));
      });
      return $result;
    }
  }
