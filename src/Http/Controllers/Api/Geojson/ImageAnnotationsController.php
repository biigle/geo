<?php
namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use DB;
use Biigle\Image;
use Biigle\Modules\Geo\Src\Libraries\LabelCoordinates;
use GeoJson\Feature\FeatureCollection;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class ImageAnnotationsController extends Controller {

  /**
   * Get GeoJson data all Labels of the specifies Image.
   * @api {get} geojson/images/{id}/annotations Get GeoJson data all Labels.
   * @apiGroup GeoJson
   * @apiName IndexImageLabels
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
    $image = Image::findOrFail($id);
    $this->authorize('access', $image);

    $labels = Image::Join('annotations', 'annotations.image_id', '=', 'images.id')
                    ->join('annotation_labels', 'annotation_labels.annotation_id', '=', 'annotations.id')
                    ->join('labels', 'labels.id', '=', 'annotation_labels.label_id')
                    ->where("image_id", $id);
    $labelCoordinates = new LabelCoordinates($labels->get());
    $results = $labelCoordinates->compute();
    return new FeatureCollection($results->all());
  }
}
?>
