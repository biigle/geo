<?php
namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use DB;
use Biigle\Project;
use Biigle\Image;
use Biigle\Modules\Geo\Src\Libraries\LabelCoordinates;
use GeoJson\Feature\FeatureCollection;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class ProjectImagesAnnotationsController extends Controller {

  /**
   * Get GeoJson data of all Labels of the Images within a Specified Project.
   * @api {get} geojson/projects/{id}/annotations Get GeoJson data all Labels of images within a Project.
   * @apiGroup GeoJson
   * @apiName IndexProjectImageLabels
   * @apiPermission projectMember
   * @apiDescription Returns an object with Array of GeoJson Feature objects containing
   * Coordinate(i.e latitude and longitude),
   *
   * @apiParam {Number} id The Image ID
   * @apiSuccessExample {json} Success response:
   *
   * {
   *    "type":"FeatureCollection",
   *    "features":
   *    [
   *      {
   *        "type":"Feature",
   *        "geometry": {
   *          "type":"Point",
   *          "coordinates":[-88.46245997506956,-7.0754471074970064]},
   *        "properties":
   *        {
   *          "_id":1,
   *          "label ID":2,
   *          "Label Name":"Coral",
   *          "annotation coordinates":"lat: -7.075447107497, lng:-88.46245997507",
   *          "image_coordinate":"lat: -7.0754151300236, lng: -88.462423984086",
   *          "_filename":"20150813_224051_IMG_3331.JPG"
   *        }
   *      },
   *      {
   *        "type":"Feature",
   *        "geometry": {
   *          "type":"Point",
   *          "coordinates":[-88.46245997506956,-7.0754471074970064]},
   *        "properties":
   *        {
   *          "_id":1,
   *          "label ID":18,
   *          "Label Name":"Sponge",
   *          "annotation coordinates":"lat: -7.075447107497, lng:-88.46245997507",
   *          "image_coordinate":"lat: -7.0754151300236, lng: -88.462423984086",
   *          "_filename":"20150813_224051_IMG_3331.JPG"
   *        }
   *      }
   *    ]
   * }
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function index($id) {
    $project = Project::findOrFail($id);
    $this->authorize('access', $project);
    $labels = Image::wherein("volume_id", $project->volumes->pluck('id')->all())
                      ->join('annotations', 'annotations.image_id', '=', 'images.id')
                      ->join('annotation_labels', 'annotation_labels.annotation_id', '=', 'annotations.id')
                      ->join('labels', 'labels.id', '=', 'annotation_labels.label_id')
                      ->select('images.id as image_id','images.filename','images.attrs', 'images.lat','images.lng','annotations.points','annotation_labels.id as annotation_label_id','labels.name as label_name');
    if($labels->exists()) {
      $labels = $labels->where(function($query) {
        $columns = ["images.lat", "images.lng", "images.attrs->metadata->distance_to_ground","images.attrs->metadata->yaw"];
        foreach($columns as $column) {
          $query->whereNotNull($column);
        }
      });
      $labelCoordinates = new LabelCoordinates($labels->get());
      $results = $labelCoordinates->compute();
      return new FeatureCollection($results->all());
    }
    else {
      abort(404, "There are no images in project");
    }
  }
}
?>
