<?php
  namespace Biigle\Modules\Geo\Src\Libraries;

  use GeoJson\Feature\Feature;
  use GeoJson\Geometry\Point;
  /**
   *
   */
  class LabelCoordinates {

    function __construct($labels) {
      $this->labels = $labels;
    }

    function compute() {
      $result = $this->labels->map(function ($label) {
        $metadata = $label->metadata;
        $imageWidthM = 2 * floatval($label->metadata['distance_to_ground']);
        $annotationPoints = array_slice(json_decode($label->points), 0, 2);
        $imageCenter = [($label->width)/2, ($label->height)/2];

        # Finding Annotation Point with respect to center of the image.
        $annotationPoint = [($annotationPoints[0] - $imageCenter[0]), ($imageCenter[1] - $annotationPoints[1])];

        // Yaw specifies the clockwise rotation in degrees but the formula below expects
        // the counterclockwise angle in radians.
        $yaw = deg2rad(-floatval($metadata['yaw']));

        # X and Y Coordinate Rotations According to Yaw
        $rotatedX = $annotationPoint[0] * cos($yaw) - $annotationPoint[1] * sin($yaw);
        $rotatedY = $annotationPoint[0] * sin($yaw) + $annotationPoint[1] * cos($yaw);

        $scalingFactor = $imageWidthM/$label->width;

        # Coordinate Offset in Meters
        $coordinateOffsetMeters = array_map(function($point) use($scalingFactor) {
          return $point * $scalingFactor;
        }, [$rotatedX, $rotatedY]);

        #radius of Earth
        $R = 6378137;

        # Coordinate Offset in Radian
        $latRadian = $coordinateOffsetMeters[1]/$R;
        $lngRadian =  $coordinateOffsetMeters[0]/($R*cos((pi()*$label->lat)/180));

        # Shift the latitude and longitude to annotation point.
        $newLat = $label->lat + $latRadian * 180/pi();
        $newLng = $label->lng + $lngRadian * 180/pi();

        return new Feature(new Point([$newLng, $newLat]), [
          "Annotation Label ID" => $label->annotation_label_id,
          "image_id" => $label->image_id,
          "Label Name"=>$label->label_name,
          "annotation coordinates" => "lat: {$newLat}, lng:{$newLng}",
          "image_coordinate" => "lat: {$label->lat}, lng: {$label->lng}",
          'Image filename' => $label->filename]);
      });
      return $result;
    }
  }
