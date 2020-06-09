<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use DB;
use Biigle\Volume;
use Biigle\Image;
use GeoJson\Feature\{Feature, FeatureCollection};
use GeoJson\Geometry\Point;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class VolumeImagesController extends Controller
{
  /**
   * Get GeoJson data of all the Images in a Volume.
   * @api {get} geojson/volumes/:id/images Get GeoJson Data of all Images in a Volume
   * @apiGroup GeoJson
   * @apiName VolumeIndexImages
   * @apiPermission projectMember
   * @apiDescription Returns an object with Array of GeoJson Feature objects containing
   * Coordinate(i.e latitude and longitude),
   * Properties like Image Id(as '_id'), image filename('_filename') and label counts.
   *
   * @apiParam {Number} id The volume ID
   * @apiSuccessExample {json} Success response:
   *
   * {
   *   "type":"FeatureCollection","features":
   * [
   *     {
   *       "type":"Feature",
   *       "geometry":
   *       {
   *         "type":"Point",
   *         "coordinates":[-88.461126072001,-7.0728840799798]
   *       },
   *       "properties":
   *       {
   *         "_id":351,
   *         "_filename":"20150814_050659_IMG_26342.JPG",
   *         "Sponge":1,
   *         "Other fauna":2,
   *         "Stalked crinoid":1
   *       }
   *     },
   *     {
   *       "type":"Feature",
   *       "geometry":
   *       {
   *         "type":"Point",
   *         "coordinates":[-88.464543615984,-7.0752754199823]
   *       },
   *       "properties":
   *       {
   *         "_id":20,
   *         "_filename":"20150813_225936_IMG_4449.JPG",
   *       }
   *     }
   *   ]
   * }
   * @param  int  $id
   * @return \Illuminate\Http\Response
  */
  public function index($id)
  {
    $volume = Volume::findOrFail($id);
    $this->authorize('access', $volume);

    $images = $volume->images()->select('id', 'lat', 'lng', 'filename')->get();
    $labels = $volume->images()->join('annotations', 'annotations.image_id', '=', 'images.id')
      ->join('annotation_labels', 'annotation_labels.annotation_id', '=', 'annotations.id')
      ->join('labels', 'labels.id', '=', 'annotation_labels.label_id')
      ->select('images.id', 'images.lat', 'images.lng','labels.name')->get()
      ->groupBy('id');

    $images->each(function($item, $key) use($labels){
      $item['label_count'] = $labels->has($item->id) ? $labels[$item->id]->groupBy('name')->map(function($v){
        return $v->count();
      }) : collect([]);
    });

    $features = $images->map(function($image){
      $feature = new Feature(new Point([$image->lng, $image->lat]), array_merge(['_id'=>$image->id, '_filename'=>$image->filename], $image->label_count->all()));
      return $feature;
    });

    return new FeatureCollection($features->all());
  }
}
